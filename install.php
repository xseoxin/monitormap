<?php
/**
 * Installation Script
 *
 * Run this once to set up the database and create admin account
 * IMPORTANT: Delete this file after installation!
 */

define('APP_ROOT', __DIR__);

$step = $_GET['step'] ?? 1;
$error = null;
$success = null;

// Step 1: Requirements check
function checkRequirements() {
    $requirements = [
        'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
        'Storage directory writable' => is_writable(__DIR__ . '/storage') || mkdir(__DIR__ . '/storage', 0755, true),
        'Logs directory writable' => is_writable(__DIR__ . '/logs') || mkdir(__DIR__ . '/logs', 0755, true),
    ];

    return $requirements;
}

// Step 2: Database configuration
if ($step == 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbPort = $_POST['db_port'] ?? 3306;
    $dbName = $_POST['db_name'] ?? 'maps_monitor';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_pass'] ?? '';

    try {
        // Test connection
        $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // Run migrations
        $migrationDir = __DIR__ . '/migrations';
        $migrations = glob($migrationDir . '/*.sql');
        sort($migrations);

        foreach ($migrations as $migration) {
            $sql = file_get_contents($migration);
            $pdo->exec($sql);
        }

        // Save .env file
        $envContent = "# Database Configuration\n";
        $envContent .= "DB_HOST={$dbHost}\n";
        $envContent .= "DB_PORT={$dbPort}\n";
        $envContent .= "DB_NAME={$dbName}\n";
        $envContent .= "DB_USER={$dbUser}\n";
        $envContent .= "DB_PASS={$dbPass}\n\n";
        $envContent .= "# Application Configuration\n";
        $envContent .= "APP_NAME=Google Maps Monitor\n";
        $envContent .= "APP_URL=http://localhost\n";
        $envContent .= "APP_ENV=production\n";
        $envContent .= "APP_DEBUG=false\n";
        $envContent .= "TIMEZONE=Europe/Warsaw\n\n";
        $envContent .= "# Security\n";
        $envContent .= "SESSION_LIFETIME=7200\n";
        $envContent .= "PASSWORD_MIN_LENGTH=8\n";
        $envContent .= "MAX_LOGIN_ATTEMPTS=5\n";
        $envContent .= "LOGIN_TIMEOUT=900\n\n";
        $envContent .= "# Scraper Configuration\n";
        $envContent .= "SCRAPER_PATH=" . __DIR__ . "/scraper/scraper.js\n";
        $envContent .= "NODE_PATH=/usr/bin/node\n";
        $envContent .= "SCRAPER_TIMEOUT=300\n";
        $envContent .= "MAX_RETRIES=3\n";
        $envContent .= "REQUEST_DELAY_MIN=2000\n";
        $envContent .= "REQUEST_DELAY_MAX=5000\n\n";
        $envContent .= "# Proxy Configuration\n";
        $envContent .= "PROXY_ROTATION=round-robin\n";
        $envContent .= "PROXY_HEALTH_CHECK_INTERVAL=3600\n";
        $envContent .= "PROXY_MAX_FAILED_ATTEMPTS=3\n";
        $envContent .= "PROXY_TIMEOUT=10000\n\n";
        $envContent .= "# Logging\n";
        $envContent .= "LOG_LEVEL=INFO\n";
        $envContent .= "LOG_MAX_FILES=90\n";
        $envContent .= "LOG_COMPRESS_AFTER=7\n\n";
        $envContent .= "# Limits\n";
        $envContent .= "MAX_PHRASES_PER_USER=50\n";
        $envContent .= "MAX_RESULTS_PER_SCAN=1000\n";
        $envContent .= "RESULTS_RETENTION_DAYS=180\n";

        file_put_contents(__DIR__ . '/.env', $envContent);

        $success = "Database created and migrations run successfully!";
        header('Location: install.php?step=3');
        exit;

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Step 3: Create admin account
if ($step == 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/includes/init.php';

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $auth = new Auth();
        $result = $auth->register($username, $email, $password, 'admin');

        if ($result['success']) {
            $success = "Admin account created successfully!";
            header('Location: install.php?step=4');
            exit;
        } else {
            $error = implode(', ', $result['errors']);
        }
    } catch (Exception $e) {
        $error = "Error creating admin account: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Google Maps Monitor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: #333;
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            margin: 0 5px;
            border-radius: 6px;
            font-size: 12px;
        }
        .step.active {
            background: #4CAF50;
            color: #fff;
            font-weight: 600;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        input[type="text"], input[type="email"], input[type="password"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }
        .btn:hover {
            background: #45a049;
        }
        .requirements {
            list-style: none;
        }
        .requirements li {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 4px;
        }
        .requirements li.pass {
            background: #d4edda;
            color: #155724;
        }
        .requirements li.fail {
            background: #f8d7da;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .warning strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Google Maps Monitor</h1>
            <p>Installation Wizard</p>
        </div>

        <div class="content">
            <div class="step-indicator">
                <div class="step <?= $step == 1 ? 'active' : '' ?>">1. Requirements</div>
                <div class="step <?= $step == 2 ? 'active' : '' ?>">2. Database</div>
                <div class="step <?= $step == 3 ? 'active' : '' ?>">3. Admin Account</div>
                <div class="step <?= $step == 4 ? 'active' : '' ?>">4. Complete</div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <h2>System Requirements</h2>
                <ul class="requirements">
                    <?php foreach (checkRequirements() as $req => $status): ?>
                    <li class="<?= $status ? 'pass' : 'fail' ?>">
                        <?= $status ? '✓' : '✗' ?> <?= htmlspecialchars($req) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (array_product(checkRequirements())): ?>
                <p style="margin-top: 20px;">
                    <a href="install.php?step=2" class="btn">Continue to Database Setup →</a>
                </p>
                <?php else: ?>
                <div class="alert alert-error">Please fix the requirements above before continuing.</div>
                <?php endif; ?>

            <?php elseif ($step == 2): ?>
                <h2>Database Configuration</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label>Database Port</label>
                        <input type="number" name="db_port" value="3306" required>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="maps_monitor" required>
                    </div>
                    <div class="form-group">
                        <label>Database User</label>
                        <input type="text" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass">
                    </div>
                    <button type="submit" class="btn">Create Database & Run Migrations →</button>
                </form>

            <?php elseif ($step == 3): ?>
                <h2>Create Admin Account</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,50}">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required minlength="8">
                    </div>
                    <button type="submit" class="btn">Create Admin Account →</button>
                </form>

            <?php elseif ($step == 4): ?>
                <h2>Installation Complete!</h2>
                <div class="alert alert-success">
                    <strong>Success!</strong> Your Google Maps Monitor is now installed and ready to use.
                </div>

                <h3>Next Steps:</h3>
                <ol style="padding-left: 20px; margin: 20px 0;">
                    <li>Install Node.js dependencies: <code>cd scraper && npm install</code></li>
                    <li>Set up cron jobs (see docs/CRON_SETUP.md)</li>
                    <li>Add proxies in the Proxies section</li>
                    <li>Start adding phrases to monitor</li>
                </ol>

                <div class="warning">
                    <strong>⚠️ IMPORTANT SECURITY NOTICE</strong>
                    Please delete the <code>install.php</code> file from your server immediately!
                </div>

                <p style="margin-top: 20px;">
                    <a href="/login.php" class="btn">Go to Login Page →</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
