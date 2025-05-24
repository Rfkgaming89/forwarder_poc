<?php
// DirectAdmin configuration
define('DA_SERVER', 'arrow.mxrouting.net');
define('DA_PORT', 2222);
define('DA_PROTOCOL', 'https');

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