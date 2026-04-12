<?php
require_once __DIR__ . '/config.php';
$stmt = $conn->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
var_dump($row);
var_dump(password_verify('password', $row['password_hash']));
?>