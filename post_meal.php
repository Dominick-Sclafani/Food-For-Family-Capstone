<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require "db.php";

// Check if the user is logged in and is a chef
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "chef") {
    $_SESSION["error"] = "Only registered chefs can post meals.";
    header("Location: index.php");
    exit;
}

// Check if the chef is verified before allowing meal posting
$stmt = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$stmt->bind_result($verification_status);
$stmt->fetch();
$stmt->close();

if ($verification_status !== "approved") {
    $_SESSION["error"] = "Your chef account is still pending approval.";
    header("Location: index.php");
    exit;
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debugging (Uncomment for testing form data)
    // echo "<pre>";
    // print_r($_POST);
    // print_r($_FILES);
    // echo "</pre>";
    // exit;

    $user_id = $_SESSION["user_id"];
    $username = $_SESSION["username"];
    $title = isset($_POST["title"]) ? trim($_POST["title"]) : "Untitled Meal";
    $description = isset($_POST["description"]) ? trim($_POST["description"]) : "No description provided";
    $ingredients = isset($_POST["ingredients"]) && !empty($_POST["ingredients"]) ? trim($_POST["ingredients"]) : "Not specified";
    $allergies = isset($_POST["allergens"]) && is_array($_POST["allergens"]) ? implode(", ", $_POST["allergens"]) : "None";
    $pickup_location = isset($_POST["pickup_location"]) ? trim($_POST["pickup_location"]) : "Not specified";
    $pickup_time = isset($_POST["pickup_time"]) ? trim($_POST["pickup_time"]) : date("Y-m-d H:i:s"); // Default to current time if missing

    $image_filename = null; // Default to null if no image is uploaded

    // Handle Image Upload
    if (!empty($_FILES["meal_image"]["name"])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true); // Ensure uploads directory exists
        }

        $image_filename = time() . "_" . basename($_FILES["meal_image"]["name"]);
        $target_file = $target_dir . $image_filename;
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
    }

    // Insert meal post into database
    $stmt = $conn->prepare("INSERT INTO meals (user_id, username, title, description, ingredients, allergies, pickup_location, pickup_time, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $user_id, $username, $title, $description, $ingredients, $allergies, $pickup_location, $pickup_time, $image_filename);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Meal posted successfully!";
    } else {
        $_SESSION["error"] = "Failed to post meal. Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}
?>
