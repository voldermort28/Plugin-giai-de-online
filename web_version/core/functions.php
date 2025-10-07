<?php
// web_version/core/functions.php

function redirect($url, $status = 302) {
    header("Location: " . $url, true, $status);
    exit();
}

function set_message($type, $message) {
    // Store a single message as an array containing its type and text.
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

function get_message() {
    // Retrieve the single message and then clear it.
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

function has_message() {
    // Check if the single message exists.
    return !empty($_SESSION['message']);
}