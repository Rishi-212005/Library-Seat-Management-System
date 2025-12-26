<?php
echo "<h2>PHPMailer Installation Script</h2>";

// Check if Composer is available
if (!file_exists('composer.phar') && !shell_exec('which composer')) {
    echo "<h3>Installing Composer...</h3>";
    
    // Download Composer installer
    $installerUrl = 'https://getcomposer.org/installer';
    $installer = file_get_contents($installerUrl);
    
    if ($installer === false) {
        die("❌ Failed to download Composer installer. Please install manually.");
    }
    
    file_put_contents('composer-setup.php', $installer);
    
    // Run Composer installer
    $output = shell_exec('php composer-setup.php');
    echo "<pre>$output</pre>";
    
    // Clean up
    unlink('composer-setup.php');
    
    if (!file_exists('composer.phar')) {
        die("❌ Composer installation failed. Please install manually.");
    }
    
    echo "✅ Composer installed successfully!<br>";
}

// Install PHPMailer
echo "<h3>Installing PHPMailer...</h3>";

$composerCommand = file_exists('composer.phar') ? 'php composer.phar' : 'composer';
$output = shell_exec("$composerCommand install 2>&1");

echo "<pre>$output</pre>";

// Check if PHPMailer was installed
if (file_exists('vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    echo "<p style='color: green;'><strong>✅ PHPMailer installed successfully!</strong></p>";
    echo "<p>You can now configure your email settings in <code>includes/email-functions.php</code></p>";
    
    // Create vendor directory structure if needed
    if (!file_exists('vendor/autoload.php')) {
        echo "<p style='color: orange;'>⚠️ Autoloader not found. Running composer dump-autoload...</p>";
        $output = shell_exec("$composerCommand dump-autoload 2>&1");
        echo "<pre>$output</pre>";
    }
    
} else {
    echo "<p style='color: red;'><strong>❌ PHPMailer installation failed!</strong></p>";
    echo "<p>Please install manually using: <code>composer require phpmailer/phpmailer</code></p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Update your Gmail credentials in <code>includes/email-functions.php</code></li>";
echo "<li>Test the email configuration by visiting <code>test-email.php</code></li>";
echo "<li>Make sure your Gmail account has 2FA enabled and an App Password generated</li>";
echo "</ol>";
?>
