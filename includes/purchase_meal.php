<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "../db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "Please log in to purchase a meal.";
    header("Location: ../login.php");
    exit;
}

// Check if meal_id is provided
if (!isset($_POST["meal_id"])) {
    $_SESSION["error"] = "Invalid meal selection.";
    header("Location: ../index.php");
    exit;
}

$meal_id = intval($_POST["meal_id"]);
$user_id = $_SESSION["user_id"];

// Check if meal exists and is available
$stmt = $conn->prepare("SELECT * FROM meals WHERE id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Meal not found.";
    header("Location: ../index.php");
    exit;
}

$meal = $result->fetch_assoc();
$stmt->close();

// Check if user has already purchased this meal
$check_purchase = $conn->prepare("SELECT * FROM purchases WHERE user_id = ? AND meal_id = ?");
$check_purchase->bind_param("ii", $user_id, $meal_id);
$check_purchase->execute();
$purchase_result = $check_purchase->get_result();

if ($purchase_result->num_rows > 0) {
    $_SESSION["error"] = "You have already purchased this meal.";
    header("Location: ../meal_details.php?id=" . $meal_id);
    exit;
}

$check_purchase->close();

// Insert purchase record
$stmt = $conn->prepare("INSERT INTO purchases (user_id, meal_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $meal_id);

if ($stmt->execute()) {
    $_SESSION["success"] = "Meal purchased successfully!";
} else {
    $_SESSION["error"] = "Error purchasing meal. Please try again.";
}

$stmt->close();
$conn->close();

header("Location: ../meal_details.php?id=" . $meal_id);
exit;
