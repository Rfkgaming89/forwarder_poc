<?php
class DirectAdminAPI {
    private $server;
    private $port;
    private $protocol;
    private $username;
    private $password;
    private $session_id;
    
    public function __construct($server, $port = 2222, $protocol = 'https') {
        $this->server = $server;
        $this->port = $port;
        $this->protocol = $protocol;
    }
    
    public function login($username, $password) {
        $this->username = $username;
        $this->password = $password;
        
        // Test authentication by getting user info
        $response = $this->makeRequest('CMD_API_SHOW_USER_CONFIG', []);
        
        if ($response === false || (isset($response['error']) && $response['error'] == '1')) {
            return false;
        }
        
        // Store credentials in session for persistent login
        $_SESSION['da_username'] = $username;
        $_SESSION['da_password'] = $password;
        $_SESSION['logged_in'] = true;
        
        return true;
    }
    
    public function logout() {
        unset($_SESSION['da_username']);
        unset($_SESSION['da_password']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['da_server']);
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true 
               && isset($_SESSION['da_username']) && isset($_SESSION['da_password']);
    }
    
    public function restoreSession() {
        if ($this->isLoggedIn()) {
            $this->username = $_SESSION['da_username'];
            $this->password = $_SESSION['da_password'];
            return true;
        }
        return false;
    }
    
    public function getDomains() {
        $response = $this->makeRequest('CMD_API_SHOW_DOMAINS', []);
        
        if ($response === false) {
            return false;
        }
        
        $domains = [];
        
        // Handle different response formats from DirectAdmin
        if (isset($response['list'])) {
            if (is_string($response['list'])) {
                $domains = explode('&', $response['list']);
                $domains = array_map(function($domain) {
                    return urldecode($domain);
                }, $domains);
            } elseif (is_array($response['list'])) {
                $domains = $response['list'];
            }
        } else {
            // If no 'list' key, try to extract domains from response keys
            foreach ($response as $key => $value) {
                if ($key !== 'error' && $key !== 'text' && $key !== 'details' && strpos($key, '.') !== false) {
                    $domains[] = $key;
                }
            }
        }
        
        return $domains;
    }
    
    public function getForwarders($domain) {
        $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', [
            'domain' => $domain
        ]);
        
        error_log('DirectAdmin getForwarders response: ' . print_r($response, true));
        
        if ($response === false) {
            return false;
        }
        
        $forwarders = [];
        foreach ($response as $key => $value) {
            if ($key !== 'error' && $key !== 'text' && $key !== 'details') {
                // Handle both string and array values
                if (is_string($value)) {
                    $forwarders[$key] = explode(',', $value);
                } elseif (is_array($value)) {
                    $forwarders[$key] = $value;
                } else {
                    $forwarders[$key] = [$value];
                }
            }
        }
        
        return $forwarders;
    }
    
    public function addForwarder($domain, $user, $forwardTo) {
        // Try the standard DirectAdmin forwarder creation endpoint
        $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', [
            'domain' => $domain,
            'action' => 'create',
            'user' => $user,
            'email' => $forwardTo
        ]);
        
        // Log the response for debugging
        error_log('DirectAdmin addForwarder response (create): ' . print_r($response, true));
        
        // If create doesn't work, try 'add'
        if ($response === false || (isset($response['error']) && $response['error'] != '0')) {
            $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', [
                'domain' => $domain,
                'action' => 'add',
                'user' => $user,
                'email' => $forwardTo
            ]);
            
            error_log('DirectAdmin addForwarder response (add): ' . print_r($response, true));
        }
        
        // Also try with different parameter names
        if ($response === false || (isset($response['error']) && $response['error'] != '0')) {
            $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', [
                'domain' => $domain,
                'action' => 'create',
                'from' => $user,
                'to' => $forwardTo
            ]);
            
            error_log('DirectAdmin addForwarder response (from/to): ' . print_r($response, true));
        }
        
        if ($response === false) {
            error_log('DirectAdmin addForwarder: All requests failed');
            return false;
        }
        
        if (isset($response['error']) && $response['error'] != '0') {
            error_log('DirectAdmin addForwarder error: ' . ($response['text'] ?? 'Unknown error'));
            return false;
        }
        
        return true;
    }
    
    public function deleteForwarder($domain, $user) {
        // Try different delete approaches
        $methods = [
            ['action' => 'delete', 'user' => $user],
            ['action' => 'delete', 'select0' => $user],
            ['delete' => 'Delete', 'select0' => $user],
            ['action' => 'delete', 'from' => $user]
        ];
        
        foreach ($methods as $index => $params) {
            $params['domain'] = $domain;
            $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', $params);
            
            error_log("DirectAdmin deleteForwarder attempt $index response: " . print_r($response, true));
            
            if ($response !== false && (!isset($response['error']) || $response['error'] == '0')) {
                // Verify the deletion by checking if forwarder still exists
                $forwarders = $this->getForwarders($domain);
                if ($forwarders !== false && !isset($forwarders[$user])) {
                    error_log("Delete successful - forwarder $user no longer exists");
                    return true;
                } else {
                    error_log("Delete claimed success but forwarder $user still exists");
                }
            }
        }
        
        error_log('DirectAdmin deleteForwarder: All methods failed');
        return false;
    }
    
    public function modifyForwarder($domain, $user, $forwardTo) {
        $response = $this->makeRequest('CMD_API_EMAIL_FORWARDERS', [
            'domain' => $domain,
            'action' => 'modify',
            'user' => $user,
            'email' => $forwardTo
        ]);
        
        error_log('DirectAdmin modifyForwarder response: ' . print_r($response, true));
        
        if ($response === false) {
            error_log('DirectAdmin modifyForwarder: Request failed');
            return false;
        }
        
        if (isset($response['error']) && $response['error'] != '0') {
            error_log('DirectAdmin modifyForwarder error: ' . ($response['text'] ?? 'Unknown error'));
            return false;
        }
        
        return true;
    }
    
    private function makeRequest($command, $data = []) {
        $url = $this->protocol . '://' . $this->server . ':' . $this->port . '/' . $command;
        
        $postData = http_build_query($data);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch) || $httpCode !== 200) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Parse DirectAdmin response
        parse_str($response, $parsed);
        return $parsed;
    }
}
?>