<?php
/**
 * Debug Log Viewer
 *
 * Simple page to view installation debug logs
 * DELETE THIS FILE after successful installation!
 */

$logFile = __DIR__ . '/storage/temp/install_debug.log';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Debug Log</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background: #2d2d30;
            padding: 20px;
            border-bottom: 2px solid #3e3e42;
        }
        .header h1 {
            color: #fff;
            font-size: 20px;
            margin-bottom: 10px;
        }
        .header p {
            color: #999;
            font-size: 14px;
        }
        .log-content {
            padding: 20px;
            overflow-x: auto;
        }
        .log-line {
            padding: 5px 0;
            border-bottom: 1px solid #3e3e42;
            font-size: 13px;
            line-height: 1.6;
        }
        .log-line:last-child {
            border-bottom: none;
        }
        .timestamp {
            color: #4ec9b0;
            margin-right: 10px;
        }
        .error {
            color: #f48771;
            background: rgba(244, 135, 113, 0.1);
            padding: 2px 4px;
            border-radius: 3px;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #dcdcaa;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .actions {
            padding: 20px;
            background: #2d2d30;
            border-top: 2px solid #3e3e42;
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            background: #0e639c;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #1177bb;
        }
        .btn-danger {
            background: #d9534f;
        }
        .btn-danger:hover {
            background: #c9302c;
        }
        .file-info {
            background: #3e3e42;
            padding: 10px 20px;
            font-size: 12px;
            color: #999;
        }
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐛 Installation Debug Log</h1>
            <p>This log shows detailed information about the installation process</p>
        </div>

        <?php if (file_exists($logFile)): ?>
            <div class="file-info">
                📄 File: <?= htmlspecialchars($logFile) ?> |
                📊 Size: <?= number_format(filesize($logFile)) ?> bytes |
                🕐 Modified: <?= date('Y-m-d H:i:s', filemtime($logFile)) ?>
            </div>

            <div class="log-content">
                <?php
                $content = file_get_contents($logFile);
                if (empty(trim($content))):
                ?>
                    <div class="empty">
                        <p>📝 Log file is empty</p>
                        <p style="margin-top: 10px; font-size: 12px;">Try running the installation Step 3 again</p>
                    </div>
                <?php else: ?>
                    <pre><?php
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        if (empty(trim($line))) continue;

                        // Highlight different types of messages
                        if (stripos($line, 'ERROR') !== false || stripos($line, 'EXCEPTION') !== false || stripos($line, 'FATAL') !== false) {
                            echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
                        } elseif (stripos($line, 'SUCCESS') !== false) {
                            echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
                        } elseif (stripos($line, 'WARNING') !== false) {
                            echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
                        } else {
                            echo htmlspecialchars($line) . "\n";
                        }
                    }
                    ?></pre>
                <?php endif; ?>
            </div>

            <div class="actions">
                <button onclick="location.reload()" class="btn">🔄 Refresh Log</button>
                <a href="/install.php?step=3" class="btn">← Back to Installation</a>
                <button onclick="if(confirm('Clear log file?')) { window.location.href='?clear=1'; }" class="btn btn-danger">🗑️ Clear Log</button>
            </div>
        <?php else: ?>
            <div class="log-content">
                <div class="empty">
                    <p>❌ Log file not found</p>
                    <p style="margin-top: 10px; font-size: 12px;">Expected location: <?= htmlspecialchars($logFile) ?></p>
                    <p style="margin-top: 10px;">
                        <a href="/install.php?step=3" class="btn">Go to Installation Step 3</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Handle clear action
    if (isset($_GET['clear']) && file_exists($logFile)) {
        unlink($logFile);
        echo '<script>alert("Log cleared!"); window.location.href="debug_log.php";</script>';
    }
    ?>
</body>
</html>
