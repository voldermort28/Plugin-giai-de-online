<?php
// web_version/core/functions.php

function redirect($url, $status = 302) {
    header("Location: " . $url, true, $status);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user() {
    if (is_logged_in()) {
        return $_SESSION['user'];
    }
    return null;
}

function has_role($role) {
    $user = current_user();
    return $user && $user['role'] === $role;
}

function set_message($type, $message) {
    $_SESSION['messages'][$type] = $message;
}

function get_message($type) {
    if (isset($_SESSION['messages'][$type])) {
        $message = $_SESSION['messages'][$type];
        unset($_SESSION['messages'][$type]);
        return $message;
    }
    return null;
}