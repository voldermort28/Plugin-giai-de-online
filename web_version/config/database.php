<?php
// web_version/config/database.php

define('DB_HOST', 'localhost');
define('DB_USER', 'laboon_user'); // <-- THAY ĐỔI
define('DB_PASS', 'Pokemon@2808'); // <-- THAY ĐỔI
define('DB_NAME', 'laboon_kiemtra'); // <-- THAY ĐỔI

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}