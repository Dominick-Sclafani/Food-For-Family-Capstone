<?php
session_start();
require "db.php";

if (!isset($_SESSION["username"])) {
    $_SESSION["error"] = "You must be logged in to apply as a chef.";
    header("Location: index.php");
    exit;
}

$username = $_SESSION["username"];
$full_name = trim($_POST["full_name"]);
$age = intval($_POST["age"]);
$reason = trim($_POST["reason"]);
$id_document = null; // Default value

// Ensure the user meets the age requirement
if ($age < 23) {
    $_SESSION["error"] = "You must be at least 23 years old to become a chef.";
    header("Location: index.php");
    exit;
}

// Handle ID Document Upload
if (!empty($_FILES["id_document"]["name"])) {
    $target_dir = "uploads/ids/"; // Folder for ID uploads
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $id_filename = basename($_FILES["id_document"]["name"]);
    $target_file = $target_dir . time() . "_" . $id_filename; // Unique filename
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $allowed_types = ["jpg", "jpeg", "png", "pdf"];

    if (!in_array($file_type, $allowed_types)) {
        $_SESSION["error"] = "Only JPG, JPEG, PNG, and PDF files are allowed for ID upload.";
        header("Location: index.php");
        exit;
    }

    if ($_FILES["id_document"]["size"] > 5000000) { // 5MB limit
        $_SESSION["error"] = "ID document size too large (Max: 5MB).";
        header("Location: index.php");
        exit;
    }

    if (!move_uploaded_file($_FILES["id_document"]["tmp_name"], $target_file)) {
        $_SESSION["error"] = "Failed to upload ID document.";
        header("Location: index.php");
        exit;
    }

    $id_document = basename($target_file); // Store only the filename in the database
}

// Store application in database (set verification_status to 'pending')
$stmt = $conn->prepare("UPDATE users SET role = 'chef', verification_status = 'pending' WHERE username = ?");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    $_SESSION["role"] = "chef";
    $_SESSION["success"] = "Your chef application has been submitted and is pending admin approval.";
} else {
    $_SESSION["error"] = "Failed to submit application. Please try again.";
}

header("Location: index.php");
exit;
?>