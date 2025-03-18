<?php
session_start();
require "db.php";

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "chef") {
    $_SESSION["error"] = "Only registered chefs can post meals.";
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];
    $username = $_SESSION["username"];
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $ingredients = trim($_POST["ingredients"]);
    $allergies = trim($_POST["allergies"]);
    $pickup_location = trim($_POST["pickup_location"]);

    $image_filename = null; // Default null if no image is uploaded

    // Handle Image Upload
    if (!empty($_FILES["meal_image"]["name"])) {
        $target_dir = "uploads/";
        $image_filename = basename($_FILES["meal_image"]["name"]);
        $target_file = $target_dir . time() . "_" . $image_filename; // Unique filename
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($imageFileType, $allowed_types)) {
            $_SESSION["error"] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            header("Location: index.php");
            exit;
        }

        if ($_FILES["meal_image"]["size"] > 5000000) { // 5MB limit
            $_SESSION["error"] = "Image size too large (Max: 5MB).";
            header("Location: index.php");
            exit;
        }

        if (!move_uploaded_file($_FILES["meal_image"]["tmp_name"], $target_file)) {
            $_SESSION["error"] = "Failed to upload image.";
            header("Location: index.php");
            exit;
        }

        // Store only the filename in the database
        $image_filename = basename($target_file);
    }

    // Insert meal post into database
    $stmt = $conn->prepare("INSERT INTO meals (user_id, username, title, description, ingredients, allergies, pickup_location, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $username, $title, $description, $ingredients, $allergies, $pickup_location, $image_filename);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Meal posted successfully!";
    } else {
        $_SESSION["error"] = "Failed to post meal.";
    }

    header("Location: index.php");
    exit;
}
?>