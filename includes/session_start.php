<?php
session_start(); // Start session to track user login state
include('db.php');

if (isset($_SESSION["user_id"])) {
    $stmt = $conn->prepare("SELECT role, verification_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $stmt->bind_result($role, $verification_status);
    $stmt->fetch();
    $stmt->close();

    // Store into session for consistent access
    $_SESSION["role"] = $role;
    $_SESSION["verification_status"] = $verification_status;
}
?>