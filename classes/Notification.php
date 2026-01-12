<?php
/**
 * Notification Class
 *
 * Handles email and Telegram notifications
 */

class Notification {
    private $config;

    public function __construct() {
        $this->config = Config::load('notifications');
    }

    /**
     * Send notification
     *
     * @param string $type Type of notification (scan_completed, scan_failed, etc.)
     * @param array $data Notification data
     * @param int $userId User ID (optional)
     */
    public function send($type, $data, $userId = null) {
        // Check if this notification type is enabled
        if (!isset($this->config['notify_on'][$type]) || !$this->config['notify_on'][$type]) {
            return;
        }

        // Prepare message
        $message = $this->prepareMessage($type, $data);

        // Send email
        if ($this->config['email']['enabled']) {
            $this->sendEmail($message, $userId);
        }

        // Send Telegram
        if ($this->config['telegram']['enabled']) {
            $this->sendTelegram($message);
        }

        Logger::debug('Notification sent', [
            'type' => $type,
            'user_id' => $userId
        ], 'SYSTEM');
    }

    /**
     * Prepare notification message
     */
    private function prepareMessage($type, $data) {
        $messages = [
            'scan_completed' => [
                'subject' => 'Scan Completed: ' . ($data['phrase'] ?? 'Unknown'),
                'body' => $this->renderTemplate('scan_completed', $data)
            ],
            'scan_failed' => [
                'subject' => 'Scan Failed: ' . ($data['phrase'] ?? 'Unknown'),
                'body' => $this->renderTemplate('scan_failed', $data)
            ],
            'proxy_deactivated' => [
                'subject' => 'Proxy Deactivated',
                'body' => $this->renderTemplate('proxy_deactivated', $data)
            ],
            'new_user_registered' => [
                'subject' => 'New User Registered',
                'body' => $this->renderTemplate('new_user_registered', $data)
            ],
            'system_error' => [
                'subject' => 'System Error',
                'body' => $this->renderTemplate('system_error', $data)
            ]
        ];

        return $messages[$type] ?? [
            'subject' => 'Notification',
            'body' => 'An event occurred in your Google Maps Monitor'
        ];
    }

    /**
     * Render notification template
     */
    private function renderTemplate($template, $data) {
        $templatePath = APP_ROOT . '/templates/email/' . $template . '.php';

        if (file_exists($templatePath)) {
            ob_start();
            extract($data);
            include $templatePath;
            return ob_get_clean();
        }

        // Default template
        return $this->renderDefaultTemplate($template, $data);
    }

    /**
     * Render default template
     */
    private function renderDefaultTemplate($type, $data) {
        $output = "<h2>" . ucwords(str_replace('_', ' ', $type)) . "</h2>\n";

        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $output .= "<p><strong>" . ucwords(str_replace('_', ' ', $key)) . ":</strong> " . htmlspecialchars($value) . "</p>\n";
            }
        }

        return $output;
    }

    /**
     * Send email notification
     */
    private function sendEmail($message, $userId = null) {
        try {
            $to = [];

            // Get user email
            if ($userId) {
                $user = (new User())->getById($userId);
                if ($user) {
                    $to[] = $user['email'];
                }
            }

            // Add admin emails
            if (!empty($this->config['admin_emails'])) {
                $to = array_merge($to, $this->config['admin_emails']);
            }

            if (empty($to)) {
                return;
            }

            // Remove duplicates
            $to = array_unique($to);

            // Prepare headers
            $headers = [
                'From: ' . $this->config['email']['from_name'] . ' <' . $this->config['email']['from_email'] . '>',
                'Reply-To: ' . $this->config['email']['from_email'],
                'X-Mailer: PHP/' . phpversion(),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8'
            ];

            // Send email
            foreach ($to as $recipient) {
                mail($recipient, $message['subject'], $message['body'], implode("\r\n", $headers));
            }

            Logger::debug('Email notification sent', [
                'recipients' => count($to),
                'subject' => $message['subject']
            ], 'SYSTEM');

        } catch (Exception $e) {
            Logger::error('Failed to send email notification', [
                'error' => $e->getMessage()
            ], 'SYSTEM');
        }
    }

    /**
     * Send Telegram notification
     */
    private function sendTelegram($message) {
        try {
            $botToken = $this->config['telegram']['bot_token'];
            $chatId = $this->config['telegram']['chat_id'];

            if (empty($botToken) || empty($chatId)) {
                return;
            }

            // Prepare message text (convert HTML to plain text for Telegram)
            $text = strip_tags($message['body']);
            $text = $message['subject'] . "\n\n" . $text;

            // Send via Telegram Bot API
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                Logger::debug('Telegram notification sent', [
                    'chat_id' => $chatId
                ], 'SYSTEM');
            } else {
                Logger::warning('Failed to send Telegram notification', [
                    'http_code' => $httpCode,
                    'response' => $response
                ], 'SYSTEM');
            }

        } catch (Exception $e) {
            Logger::error('Failed to send Telegram notification', [
                'error' => $e->getMessage()
            ], 'SYSTEM');
        }
    }

    /**
     * Send scan completed notification
     */
    public function scanCompleted($scanId, $userId) {
        $scanHistory = new ScanHistory();
        $scan = $scanHistory->getById($scanId);

        if (!$scan) {
            return;
        }

        $this->send('scan_completed', [
            'phrase' => $scan['phrase'],
            'results_found' => $scan['results_found'],
            'duration' => $scan['duration_seconds'] . ' seconds',
            'scanned_points' => $scan['scanned_points'],
            'total_points' => $scan['total_grid_points'],
            'scan_id' => $scanId
        ], $userId);
    }

    /**
     * Send scan failed notification
     */
    public function scanFailed($scanId, $userId, $errorMessage) {
        $scanHistory = new ScanHistory();
        $scan = $scanHistory->getById($scanId);

        if (!$scan) {
            return;
        }

        $this->send('scan_failed', [
            'phrase' => $scan['phrase'],
            'error' => $errorMessage,
            'scan_id' => $scanId
        ], $userId);
    }

    /**
     * Send proxy deactivated notification
     */
    public function proxyDeactivated($proxyId) {
        $proxy = (new Proxy())->getById($proxyId);

        if (!$proxy) {
            return;
        }

        $this->send('proxy_deactivated', [
            'proxy_url' => $proxy['proxy_url'],
            'failed_attempts' => $proxy['failed_attempts']
        ]);
    }

    /**
     * Send new user registered notification
     */
    public function userRegistered($userId) {
        $user = (new User())->getById($userId);

        if (!$user) {
            return;
        }

        $this->send('new_user_registered', [
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ]);
    }

    /**
     * Send system error notification
     */
    public function systemError($error, $context = []) {
        $this->send('system_error', [
            'error' => $error,
            'context' => json_encode($context, JSON_PRETTY_PRINT),
            'time' => date('Y-m-d H:i:s')
        ]);
    }
}
