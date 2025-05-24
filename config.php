<?php
// DirectAdmin configuration
// Default server if none selected
define('DEFAULT_DA_SERVER', 'arrow.mxrouting.net');
define('DA_PORT', 2222);
define('DA_PROTOCOL', 'https');

// Available servers
define('DA_SERVERS', [
    'eagle.mxlogin.com',
    'pixel.mxrouting.net',
    'taylor.mxrouting.net',
    'sunfire.mxrouting.net',
    'blizzard.mxrouting.net',
    'longhorn.mxrouting.net',
    'safari.mxrouting.net',
    'lucy.mxrouting.net',
    'arrow.mxrouting.net',
    'echo.mxrouting.net',
    'london.mxroute.com',
    'shadow.mxrouting.net',
    'moose.mxrouting.net',
    'tuesday.mxrouting.net',
    'monday.mxrouting.net',
    'wednesday.mxrouting.net',
    'redbull.mxrouting.net',
    'witcher.mxrouting.net',
    'heracles.mxrouting.net',
    'everest.mxrouting.net',
    'glacier.mxrouting.net'
]);

// Get current server from session or use default
define('DA_SERVER', $_SESSION['da_server'] ?? DEFAULT_DA_SERVER);

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Start session
session_start();
session_regenerate_id(true);

// Set session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();
?>