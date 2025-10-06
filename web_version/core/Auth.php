<?php
// web_version/core/Auth.php

class Auth {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function login($username, $password) {
        $user = $this->db->fetch("SELECT * FROM users WHERE username = ?", [$username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'display_name' => $user['display_name'],
                'role' => $user['role']
            ];
            return true;
        }
        return false;
    }

    public function logout() {
        session_unset();
        session_destroy();
    }

    public function check() {
        return isset($_SESSION['user_id']);
    }

    public function user() {
        return $_SESSION['user'] ?? null;
    }

    public function hasRole($role) {
        $user = $this->user();
        return $user && $user['role'] === $role;
    }

    public function createUser($username, $password, $display_name, $role = 'grader') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->db->insert('users', [
            'username' => $username,
            'password_hash' => $password_hash,
            'display_name' => $display_name,
            'role' => $role
        ]);
    }

    public function updateUser($user_id, $display_name, $role, $password = null) {
        $data = [
            'display_name' => $display_name,
            'role' => $role
        ];

        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $this->db->update('users', $data, 'user_id = ?', [$user_id]);
    }
}