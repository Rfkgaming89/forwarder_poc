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

// Get domains
$domains = $da->getDomains();
if ($domains === false) {
    $error = 'Failed to retrieve domains. Please check your connection.';
    $domains = [];
}

$selectedDomain = $_GET['domain'] ?? '';
$forwarders = [];

if ($selectedDomain && in_array($selectedDomain, $domains)) {
    $forwarders = $da->getForwarders($selectedDomain);
    if ($forwarders === false) {
        $error = 'Failed to retrieve forwarders for ' . htmlspecialchars($selectedDomain);
        $forwarders = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DirectAdmin Dashboard - Email Forwarders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
            margin-top: 0;
        }
        .domain-selector {
            margin-bottom: 30px;
        }
        select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-width: 200px;
        }
        .forwarder-list {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 2px;
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
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
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
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        .logout-btn {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
        }
        .logout-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Email Forwarder Manager</h1>
        <div style="font-size: 14px; color: #666; margin-top: 5px;">Server: <?= htmlspecialchars(DA_SERVER) ?></div>
        <a href="index.php?action=logout" class="logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="domain-selector">
            <h2>Select Domain</h2>
            <form method="GET">
                <select name="domain" onchange="this.form.submit()">
                    <option value="">-- Select a domain --</option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?= htmlspecialchars($domain) ?>" 
                                <?= $selectedDomain === $domain ? 'selected' : '' ?>>
                            <?= htmlspecialchars($domain) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        
        <?php if ($selectedDomain): ?>
            <div class="forwarder-list">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2>Email Forwarders for <?= htmlspecialchars($selectedDomain) ?></h2>
                    <a href="manage_forwarder.php?domain=<?= urlencode($selectedDomain) ?>&action=add" 
                       class="btn btn-success">Add New Forwarder</a>
                </div>
                
                <?php if (empty($forwarders)): ?>
                    <div class="no-data">
                        No email forwarders found for this domain.
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Email Address</th>
                                <th>Forward To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forwarders as $user => $destinations): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user) ?>@<?= htmlspecialchars($selectedDomain) ?></td>
                                    <td><?= htmlspecialchars(implode(', ', $destinations)) ?></td>
                                    <td>
                                        <a href="manage_forwarder.php?domain=<?= urlencode($selectedDomain) ?>&user=<?= urlencode($user) ?>&action=edit" 
                                           class="btn btn-primary">Edit</a>
                                        <a href="manage_forwarder.php?domain=<?= urlencode($selectedDomain) ?>&user=<?= urlencode($user) ?>&action=delete" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this forwarder?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>