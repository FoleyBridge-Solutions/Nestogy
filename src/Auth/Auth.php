<?php

namespace Twetech\Nestogy\Auth;

class Auth {
    protected $pdo;
    protected $cookieDuration = 30 * 24 * 60 * 60; // 30 days

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public static function check() {
        return isset($_SESSION['user_id']);
    }

    public function login($user_id, $user_role = 'admin', $user_avatar = null, $remember_me = false) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $user_role;
        $_SESSION['logged'] = true;
        $_SESSION['user_avatar'] = $user_avatar;

        if ($remember_me) {
            $token = bin2hex(random_bytes(16));
            $token_hash = password_hash($token, PASSWORD_DEFAULT);

            // Store the token in the remember_tokens table
            $stmt = $this->pdo->prepare('INSERT INTO remember_tokens (remember_token_token, remember_token_user_id, remember_token_created_at) VALUES (:token, :user_id, NOW())');
            $stmt->execute(['token' => $token_hash, 'user_id' => $user_id]);

            // Set a cookie with the token
            setcookie('remember_me', "$user_id:$token", time() + $this->cookieDuration, '/', '', true, true);
        }

        header('Location: /public/');
        exit;
    }

    public static function logout($pdo) {
        // Clear the session
        unset($_SESSION['user_id']);
        session_destroy();
    
        // Clear the remember me cookie
        setcookie('remember_me', '', time() - 3600, '/', '', true, true);
    
        // Optionally, delete the token from the database
        if (isset($_COOKIE['remember_me'])) {
            list($user_id, $token) = explode(':', $_COOKIE['remember_me']);
            $stmt = $pdo->prepare('DELETE FROM remember_tokens WHERE remember_token_user_id = :user_id');
            $stmt->execute(['user_id' => $user_id]);
        }
    
        header('Location: login.php');
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
                $this->login($user_id, $this->getUserRole($user_id), $this->getUserAvatar($user_id), true);
            }
        }
    }

    public function getUserAvatar($user_id) {
        $stmt = $this->pdo->prepare('SELECT user_avatar FROM users WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchColumn();
    }

    public function findUser($email, $password) {
        $stmt = $this->pdo->prepare('SELECT * FROM users LEFT JOIN user_settings ON user_settings.user_id = users.user_id WHERE user_email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['user_password'])) {
            return [
                'user_id' => $user['user_id'],
                'user_role' => $user['user_role'],
                'user_token' => $user['user_token'] ?? null,
                'user_avatar' => $user['user_avatar'] ?? null,
            ];
        } else {
            return false;
        }
    }

    protected function getUserRole($user_id) {
        $stmt = $this->pdo->prepare('SELECT user_role FROM user_settings WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchColumn();
    }

    public function getUser($user_id) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :user_id LEFT JOIN user_settings ON user_settings.user_id = users.user_id');
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetch();
    }

    public function getUsers() {
        $stmt = $this->pdo->prepare('SELECT * FROM users LEFT JOIN user_settings ON user_settings.user_id = users.user_id');
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
}
