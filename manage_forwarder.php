<?php
require_once 'config.php';
require_once 'DirectAdminAPI.php';

$da = new DirectAdminAPI(DA_SERVER, DA_PORT, DA_PROTOCOL);

// Check if logged in
if (!$da->restoreSession()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$domain = $_GET['domain'] ?? '';
$user = $_GET['user'] ?? '';
$action = $_GET['action'] ?? '';

// Validate domain
$domains = $da->getDomains();
if (!in_array($domain, $domains)) {
    header('Location: dashboard.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $newUser = trim($_POST['user'] ?? '');
        $forwardTo = trim($_POST['forward_to'] ?? '');
        
        if (empty($newUser) || empty($forwardTo)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($forwardTo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address to forward to.';
        } else {
            $result = $da->addForwarder($domain, $newUser, $forwardTo);
            error_log('Add forwarder result for ' . $newUser . '@' . $domain . ' -> ' . $forwardTo . ': ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                $success = 'Email forwarder added successfully.';
                // Redirect after success to prevent re-submission
                header('Location: dashboard.php?domain=' . urlencode($domain));
                exit;
            } else {
                $error = 'Failed to add email forwarder. Check the server error log for details.';
            }
        }
    } elseif ($action === 'edit') {
        $forwardTo = trim($_POST['forward_to'] ?? '');
        
        if (empty($forwardTo)) {
            $error = 'Please enter an email address to forward to.';
        } elseif (!filter_var($forwardTo, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address to forward to.';
        } else {
            if ($da->modifyForwarder($domain, $user, $forwardTo)) {
                $success = 'Email forwarder updated successfully.';
                // Redirect after success
                header('Location: dashboard.php?domain=' . urlencode($domain));
                exit;
            } else {
                $error = 'Failed to update email forwarder. Please try again.';
            }
        }
    } elseif ($action === 'delete' && isset($_POST['confirm_delete'])) {
        if ($da->deleteForwarder($domain, $user)) {
            $success = 'Email forwarder deleted successfully.';
            // Redirect after success
            header('Location: dashboard.php?domain=' . urlencode($domain));
            exit;
        } else {
            $error = 'Failed to delete email forwarder. Please try again.';
        }
    }
}

// Get current forwarder data for editing
$currentForwarder = '';
if ($action === 'edit' && $user) {
    $forwarders = $da->getForwarders($domain);
    if (isset($forwarders[$user])) {
        $currentForwarder = implode(', ', $forwarders[$user]);
    }
}

$pageTitle = ucfirst($action) . ' Email Forwarder';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - DirectAdmin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-primary {
            background-color: #007cba;
            color: white;
        }
        .btn-primary:hover {
            background-color: #005a87;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .error {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            color: #2e7d32;
            background-color: #e8f5e8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .domain-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            color: #666;
        }
        .confirm-delete {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .confirm-delete h3 {
            color: #856404;
            margin-top: 0;
        }
        .help-text {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        
        <div class="domain-info">
            <strong>Domain:</strong> <?= htmlspecialchars($domain) ?>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'add'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="user">Email Username:</label>
                    <input type="text" id="user" name="user" required 
                           value="<?= htmlspecialchars($_POST['user'] ?? '') ?>"
                           placeholder="e.g., info, contact, support">
                    <div class="help-text">This will create: [username]@<?= htmlspecialchars($domain) ?></div>
                </div>
                
                <div class="form-group">
                    <label for="forward_to">Forward To:</label>
                    <input type="email" id="forward_to" name="forward_to" required 
                           value="<?= htmlspecialchars($_POST['forward_to'] ?? '') ?>"
                           placeholder="destination@example.com">
                    <div class="help-text">Enter the email address where messages should be forwarded</div>
                </div>
                
                <button type="submit" class="btn-primary">Add Forwarder</button>
                <a href="dashboard.php?domain=<?= urlencode($domain) ?>" class="btn-secondary">Cancel</a>
            </form>
            
        <?php elseif ($action === 'edit'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="text" value="<?= htmlspecialchars($user) ?>@<?= htmlspecialchars($domain) ?>" 
                           readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label for="forward_to">Forward To:</label>
                    <input type="email" id="forward_to" name="forward_to" required 
                           value="<?= htmlspecialchars($_POST['forward_to'] ?? $currentForwarder) ?>"
                           placeholder="destination@example.com">
                    <div class="help-text">Enter the email address where messages should be forwarded</div>
                </div>
                
                <button type="submit" class="btn-primary">Update Forwarder</button>
                <a href="dashboard.php?domain=<?= urlencode($domain) ?>" class="btn-secondary">Cancel</a>
            </form>
            
        <?php elseif ($action === 'delete'): ?>
            <div class="confirm-delete">
                <h3>⚠️ Confirm Deletion</h3>
                <p>Are you sure you want to delete the email forwarder:</p>
                <p><strong><?= htmlspecialchars($user) ?>@<?= htmlspecialchars($domain) ?></strong></p>
                <p>This action cannot be undone.</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="confirm_delete" value="1">
                <button type="submit" class="btn-danger">Yes, Delete Forwarder</button>
                <a href="dashboard.php?domain=<?= urlencode($domain) ?>" class="btn-secondary">Cancel</a>
            </form>
            
        <?php else: ?>
            <div class="error">Invalid action specified.</div>
            <a href="dashboard.php?domain=<?= urlencode($domain) ?>" class="btn-secondary">Back to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>