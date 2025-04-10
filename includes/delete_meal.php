<?php
session_start();
require "../db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "Please log in to delete meals.";
    header("Location: ../login.php");
    exit;
}

// Check if meal ID is provided
if (!isset($_POST["meal_id"])) {
    $_SESSION["error"] = "No meal selected.";
    header("Location: ../index.php");
    exit;
}

$meal_id = $_POST["meal_id"];

// Verify the meal belongs to the current user
$stmt = $conn->prepare("SELECT user_id FROM meals WHERE id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Meal not found.";
    header("Location: ../index.php");
    exit;
}

$meal = $result->fetch_assoc();
if ($meal["user_id"] != $_SESSION["user_id"]) {
    $_SESSION["error"] = "You don't have permission to delete this meal.";
    header("Location: ../index.php");
    exit;
}

// Delete the meal
$stmt = $conn->prepare("DELETE FROM meals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $meal_id, $_SESSION["user_id"]);

if ($stmt->execute()) {
    $_SESSION["success"] = "Meal deleted successfully.";
} else {
    $_SESSION["error"] = "Error deleting meal. Please try again.";
}

$stmt->close();
header("Location: ../index.php");
exit;
?>