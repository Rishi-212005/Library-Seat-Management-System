<?php
require_once 'includes/functions.php';

echo "<h1>System Diagnostic</h1>";

// Check GD Extension
echo "<h2>GD Extension Status</h2>";
if (extension_loaded('gd')) {
    echo "<p style='color: green;'>✓ GD Extension is ENABLED</p>";
    $info = gd_info();
    echo "<pre>";
    print_r($info);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ GD Extension is DISABLED</p>";
    echo "<p>The system will use fallback compression method.</p>";
}

// Check file upload settings
echo "<h2>PHP Upload Settings</h2>";
echo "<p><strong>Max file size:</strong> " . ini_get('upload_max_filesize') . "</p>";
echo "<p><strong>Max post size:</strong> " . ini_get('post_max_size') . "</p>";
echo "<p><strong>Memory limit:</strong> " . ini_get('memory_limit') . "</p>";

// Test image processing
echo "<h2>Image Processing Test</h2>";
if (isset($_POST['test_upload']) && isset($_FILES['test_image'])) {
    $result = validateAndProcessImage($_FILES['test_image']);
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Image processing successful!</p>";
        echo "<p>Method used: " . $result['method'] . "</p>";
        echo "<p>Compressed size: " . strlen($result['data']) . " characters</p>";
    } else {
        echo "<p style='color: red;'>✗ Image processing failed: " . $result['error'] . "</p>";
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h3>Test Image Upload</h3>
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit" name="test_upload">Test Upload</button>
</form>

<h2>Quick Fixes</h2>
<div style="background: #f0f0f0; padding: 15px; margin: 10px 0;">
    <h3>Option 1: Enable GD Extension</h3>
    <ol>
        <li>Open <code>C:\xampp\php\php.ini</code></li>
        <li>Find <code>;extension=gd</code></li>
        <li>Change to <code>extension=gd</code> (remove semicolon)</li>
        <li>Restart Apache in XAMPP</li>
        <li>Refresh this page</li>
    </ol>
</div>

<div style="background: #e8f4fd; padding: 15px; margin: 10px 0;">
    <h3>Option 2: Use Current System (No GD Required)</h3>
    <p>The updated code will work without GD using simple compression.</p>
    <p>Just make sure to use images smaller than 1MB.</p>
</div>
