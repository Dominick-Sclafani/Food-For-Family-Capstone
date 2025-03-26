<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "../db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "regular") {
    $_SESSION["error"] = "Only customers can purchase meals.";
    header("Location: ../index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["meal_id"])) {
    $meal_id = intval($_POST["meal_id"]);
    $user_id = $_SESSION["user_id"];

    // Check if already purchased
    $check = $conn->prepare("SELECT * FROM purchases WHERE user_id = ? AND meal_id = ?");
    $check->bind_param("ii", $user_id, $meal_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $_SESSION["error"] = "You have already purchased this meal.";
        header("Location: ../meal_details.php?id=" . $meal_id);
        exit;
    }

    // Insert purchase
    $stmt = $conn->prepare("INSERT INTO purchases (user_id, meal_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $meal_id);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Purchase successful!";
    } else {
        $_SESSION["error"] = "Purchase failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("Location: ../meal_details.php?id=" . $meal_id);
    exit;
} else {
    $_SESSION["error"] = "Invalid request.";
    header("Location: ../index.php");
    exit;
}
