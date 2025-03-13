<?php
session_start();
require "db.php";

if (!isset($_SESSION["username"])) {
    $_SESSION["error"] = "You must be logged in to apply as a chef.";
    header("Location: index.php");
    exit;
}

$username = $_SESSION["username"];

// Update the user's role to 'chef'
$stmt = $conn->prepare("UPDATE users SET role = 'chef' WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    $_SESSION["role"] = "chef"; // Update session role
    $_SESSION["success"] = "Congratulations! You are now a chef and can post meals.";
} else {
    $_SESSION["error"] = "Failed to update role. Please try again.";
}

header("Location: index.php");
exit;
?>