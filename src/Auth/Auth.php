<?php

namespace Twetech\Nestogy\Auth;

class Auth {
    protected $pdo;
    protected $cookieDuration = 30 * 24 * 60 * 60; // 30 days

    // Define the maximum allowed failed attempts and lockout duration
    private $maxFailedAttempts = 5;
    private $lockoutTime = 900; // in seconds (15 minutes)

    private $maxIPAttempts = 30;
    private $ipLockoutTime = 3600; // in seconds (1 hour)

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public function login($user) {
        $user_id = $user['user_id'];
        $user_name = $user['user_name'];
        $user_role = $user['user_role'];
        $user_avatar = $user['user_avatar'];
        $remember_me = $user['remember_me'];
        $user_encryption_ciphertext = $user['user_specific_encryption_ciphertext'];
        $user_password = $user['user_password'];

        // Decrypt the master key using the user's password
        $site_encryption_master_key = $this->decryptUserSpecificKey($user_encryption_ciphertext, $user_password);

        // Generate a session key and store it
        generateUserSessionKey($site_encryption_master_key);

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['user_role'] = $user_role;
        $_SESSION['logged'] = true;
        $_SESSION['user_avatar'] = $user_avatar;

        if (isset($user['user_config_remember_me'])) {
            if ($remember_me) {
                $token = bin2hex(random_bytes(16));
                $token_hash = password_hash($token, PASSWORD_DEFAULT);

                // Store the token in the remember_tokens table
                $stmt = $this->pdo->prepare('INSERT INTO remember_tokens (remember_token_token, remember_token_user_id, remember_token_created_at) VALUES (:token, :user_id, NOW())');
                $stmt->execute(['token' => $token_hash, 'user_id' => $user_id]);

                // Set a cookie with the token
                setcookie(
                    'remember_me', 
                    $user['user_id'] . ':' . $token, 
                    [
                        'expires' => time() + $this->cookieDuration,
                        'path' => '/',
                        'domain' => '',  // Leave empty to use current domain
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );

                // Extend session duration
                ini_set('session.gc_maxlifetime', $this->cookieDuration);
                session_set_cookie_params([
                    'lifetime' => $this->cookieDuration,
                    'path' => '/',
                    'domain' => '',  // Leave empty to use current domain
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                // Regenerate session ID
                session_regenerate_id(true);
            }
        } else {
            error_log("User config remember me not set");
        }

        header('Location: /public/');
        exit;
    }

    private function setUserEncryptionCiphertext($user) {
        $user_encryption_ciphertext = $user['user_specific_encryption_ciphertext'];
        $user_role = $user['user_role'];
        $password = $user['user_password'];

        // Setup encryption session key
        if (isset($user_encryption_ciphertext) && $user_role > 1) {
            $site_encryption_master_key = decryptUserSpecificKey($user_encryption_ciphertext, $password);
            generateUserSessionKey($site_encryption_master_key);
        }
    }

    private function decryptUserSpecificKey($user_encryption_ciphertext, $user_password) {
        //Get the IV, salt and ciphertext
        $salt = substr($user_encryption_ciphertext, 0, 16);
        $iv = substr($user_encryption_ciphertext, 16, 16);
        $ciphertext = substr($user_encryption_ciphertext, 32);

        //Generate 128-bit (16 byte/char) kdhash of the users password
        $user_password_kdhash = hash_pbkdf2('sha256', $user_password, $salt, 100000, 16);

        //Use this hash to get the original/master key
        return openssl_decrypt($ciphertext, 'aes-128-cbc', $user_password_kdhash, 0, $iv);
    }

    public static function logout($pdo) {
        // Clear the session
        unset($_SESSION['user_id']);
        unset($_SESSION['user_encryption_session_ciphertext']);
        unset($_SESSION['user_encryption_session_iv']);
        session_destroy();

        error_log("User logged out");
    
        // Clear the remember me cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
        setcookie('user_encryption_session_key', '', time() - 3600, '/', '', true, true);
    
        // Optionally, delete the token from the database
        if (isset($_COOKIE['remember_me'])) {
            list($user_id, $token) = explode(':', $_COOKIE['remember_me']);
            $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE remember_token_user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
        }

        session_unset();
    
        header('Location: /');
        exit;
    }
    
    public function checkRememberMe() {
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
            list($user_id, $token) = explode(':', $_COOKIE['remember_me']);

            // Retrieve the token from the remember_tokens table
            $stmt = $this->pdo->prepare('SELECT remember_token_token FROM remember_tokens WHERE remember_token_user_id = :user_id ORDER BY remember_token_created_at DESC LIMIT 1');
            $stmt->execute(['user_id' => $user_id]);
            $stored_token_hash = $stmt->fetchColumn();

            if ($stored_token_hash && password_verify($token, $stored_token_hash)) {
                // Token is valid, log in the user
                $user = $this->getUser($user_id);
                if ($user) {
                    // Extend session duration
                    ini_set('session.gc_maxlifetime', $this->cookieDuration);
                    session_set_cookie_params([
                        'lifetime' => $this->cookieDuration,
                        'path' => '/',
                        'domain' => '', // Set to your domain
                        'secure' => true,
                        'httponly' => true,
                        'samesite' => 'Lax' // Or 'Strict', 'None'
                    ]);

                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    // Manually send a new session cookie with updated parameters
                    setcookie(
                        session_name(),
                        session_id(),
                        [
                            'expires' => time() + $this->cookieDuration,
                            'path' => '/',
                            'domain' => '', // Set to your domain
                            'secure' => true,
                            'httponly' => true,
                            'samesite' => 'Lax' // Or 'Strict', 'None'
                        ]
                    );

                    $user['remember_me'] = $user['user_config_remember_me'];

                    $this->login($user);
                } else {
                    error_log("User not found for user_id: $user_id");
                }
            } else {
                error_log("Token verification failed for user_id: $user_id");
            }
        } else {
            error_log("No remember_me cookie set");
        }
    }

    public function getUserAvatar($user_id) {
        $stmt = $this->pdo->prepare('SELECT user_avatar FROM users WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchColumn();
    }

    public function findUser($email, $password) {
        if ($this->isAccountLocked($email)) {
            error_log("Account locked due to too many failed login attempts: $email");
            return false;
        }

        $stmt = $this->pdo->prepare('
            SELECT * FROM users
            LEFT JOIN user_settings ON user_settings.user_id = users.user_id
            WHERE user_email = :email
        ');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['user_password'])) {
            // Successful login; clear any failed attempts
            $this->clearFailedLogins($email);

            return [
                'user_id' => $user['user_id'],
                'user_name' => $user['user_name'],
                'user_role' => $user['user_role'],
                'user_token' => $user['user_token'] ?? null,
                'user_avatar' => $user['user_avatar'] ?? null,
                'user_specific_encryption_ciphertext' => $user['user_specific_encryption_ciphertext'] ?? null,
                'user_password' => $password,
                'user_config_remember_me' => true
            ];
        } else {
            // Failed login; record the attempt
            $this->recordFailedLogin($email);
            return false;
        }
    }

    public function getUserRole($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'];
        }
        $stmt = $this->pdo->prepare('SELECT user_role FROM user_settings WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchColumn();
    }

    public function getUser($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'];
        }
        $stmt = $this->pdo->prepare('SELECT * FROM users LEFT JOIN user_settings ON user_settings.user_id = users.user_id WHERE users.user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch();
    }
    
    public function getUsername($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'];
        }
        return $this->getUser($user_id)['user_name'];
    }

    public function getUsers() {
        $stmt = $this->pdo->prepare('SELECT * FROM users LEFT JOIN user_settings ON user_settings.user_id = users.user_id ORDER BY user_archived_at ASC, user_status DESC, user_role ASC');
        $stmt->execute();
        return $stmt->fetchAll($this->pdo::FETCH_ASSOC);
    }

    public function checkClientAccess($user_id, $client_id, $type) {
        $stmt = $this->pdo->prepare('SELECT * FROM user_client_restrictions WHERE restriction_user_id = :user_id AND restriction_client_id = :client_id');
        $stmt->execute(['user_id' => $user_id, 'client_id' => $client_id]);
        $restriction = $stmt->fetch($this->pdo::FETCH_ASSOC);

        if ($restriction && $restriction['restriction_type'] == $type) {
            return false;
        }
        return true;
    }

    public function checkClassAccess($user_id, $type, $class) {
        $stmt = $this->pdo->prepare('SELECT * FROM user_class_restrictions WHERE restriction_user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        $restrictions = $stmt->fetchAll($this->pdo::FETCH_ASSOC);

        foreach ($restrictions as $restriction) {
            if (($restriction['restriction_type'] == $type && $restriction['restriction_class'] == $class) ||
                ($restriction['restriction_type'] == $class && $restriction['restriction_class'] == $type)) {
                return false;
            }
        }
        return true;
    }

    public function getCompany() {
        $company_id = 1; // TODO: Don't hardcode this
        $stmt = $this->pdo->prepare('SELECT * FROM companies WHERE company_id = :company_id');
        $stmt->execute(['company_id' => $company_id]);
        return $stmt->fetch();
    }

    public function getRecentActivitiesByUser($user_id = null) {
        if ($user_id === null) {
            $user_id = $_SESSION['user_id'];
        }
        $stmt = $this->pdo->prepare('SELECT * FROM logs
        LEFT JOIN users ON users.user_id = logs.log_user_id
        WHERE log_user_id = :user_id
        ORDER BY log_created_at DESC LIMIT 10');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll($this->pdo::FETCH_ASSOC);
    }

    public function getAllRecentActivities() {
        $stmt = $this->pdo->prepare('SELECT * FROM logs
        LEFT JOIN users ON users.user_id = logs.log_user_id
        ORDER BY log_created_at DESC LIMIT 10');
        $stmt->execute();
        $logs = $stmt->fetchAll($this->pdo::FETCH_ASSOC);
        
        //Login Attempts
        $stmt = $this->pdo->prepare('SELECT * FROM login_attempts ORDER BY attempt_time DESC LIMIT 10');
        $stmt->execute();
        $login_attempts = $stmt->fetchAll($this->pdo::FETCH_ASSOC);

        $return_data = [];

        foreach ($logs as $log) {
            $return_data[] = $log;
        }

        foreach ($login_attempts as $login_attempt) {
            $return_data[] = $login_attempt;
        }

        return $return_data;
        
    }

    public function getMailQueue($sent = false) {
        $stmt = $this->pdo->prepare('SELECT * FROM email_queue WHERE email_status = :status ORDER BY email_queued_at DESC');
        $stmt->execute(['status' => $sent ? 3 : 0]);
        return $stmt->fetchAll($this->pdo::FETCH_ASSOC);
    }

    // Record a failed login attempt
    private function recordFailedLogin($email) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $this->recordIPFailedLogin($ip);

        $stmt = $this->pdo->prepare('
            INSERT INTO login_attempts (email, attempt_time)
            VALUES (:email, NOW())
        ');
        $stmt->execute(['email' => $email]);
    }

    // Check if the account is locked
    public function isAccountLocked($email) {
        // Calculate the time window for counting failed attempts
        $timeWindow = date('Y-m-d H:i:s', time() - $this->lockoutTime);

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM login_attempts
            WHERE email = :email AND attempt_time > :timeWindow
        ');
        $stmt->execute(['email' => $email, 'timeWindow' => $timeWindow]);
        $attempts = $stmt->fetchColumn();

        // return $attempts >= $this->maxFailedAttempts;
        return false;
    }

    // Clear failed login attempts after successful login
    private function clearFailedLogins($email) {
        $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
        $stmt->execute(['email' => $email]);
    }

    private function recordIPFailedLogin($ip) {
        $stmt = $this->pdo->prepare('
            INSERT INTO ip_login_attempts (ip_address, attempt_time)
            VALUES (:ip_address, NOW())
        ');
        $stmt->execute(['ip_address' => $ip]);
    }

    public function isIPBlocked($ip) {
        $timeWindow = date('Y-m-d H:i:s', time() - $this->ipLockoutTime);

        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM ip_login_attempts
            WHERE ip_address = :ip_address AND attempt_time > :timeWindow
        ');
        $stmt->execute(['ip_address' => $ip, 'timeWindow' => $timeWindow]);
        $attempts = $stmt->fetchColumn();

        return $attempts >= $this->maxIPAttempts;
    }
}