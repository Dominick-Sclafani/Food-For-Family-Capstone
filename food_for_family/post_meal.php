<?php
session_start();
require "db.php";

if (!isset($_SESSION["username"])) {
    $_SESSION["error"] = "You must be logged in to post a meal.";
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION["username"];
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $ingredients = trim($_POST["ingredients"]);
    $allergies = trim($_POST["allergies"]);
    $pickup_location = trim($_POST["pickup_location"]);

    // Validate input
    if (empty($title) || empty($description) || empty($ingredients) || empty($allergies) || empty($pickup_location)) {
        $_SESSION["error"] = "All fields are required.";
        header("Location: index.php");
        exit;
    }

    // Get user_id based on username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($user_id);
    $stmt->fetch();

    if (!$stmt->num_rows) {
        $_SESSION["error"] = "User not found.";
        header("Location: index.php");
        exit;
    }
    $stmt->close();

    // Insert meal into database
    $stmt = $conn->prepare("INSERT INTO meals (user_id, username, title, description, ingredients, allergies, pickup_location) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $username, $title, $description, $ingredients, $allergies, $pickup_location);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Meal posted successfully!";
    } else {
        $_SESSION["error"] = "Failed to post meal. Please try again.";
    }

    header("Location: index.php");
    exit;
}
?>