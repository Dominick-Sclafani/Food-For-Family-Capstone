<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["user_id"])) {
    $user_id = $_SESSION["user_id"];
    $chef_id = $_POST["chef_id"];
    $meal_id = $_POST["meal_id"];
    $rating = $_POST["rating"];
    $comment = $_POST["comment"];

    // Verify that the user has purchased this meal from this chef
    $verify_stmt = $conn->prepare("
        SELECT 1 FROM purchases p
        JOIN meals m ON p.meal_id = m.id
        WHERE p.user_id = ? 
        AND m.id = ?
        AND m.user_id = ?
    ");
    $verify_stmt->bind_param("iii", $user_id, $meal_id, $chef_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows > 0) {
        // Check if user has already reviewed this meal
        $check_review = $conn->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND meal_id = ?");
        $check_review->bind_param("ii", $user_id, $meal_id);
        $check_review->execute();
        $review_exists = $check_review->get_result()->num_rows > 0;

        if (!$review_exists) {
            // Insert the review
            $insert_stmt = $conn->prepare("
                INSERT INTO reviews (user_id, chef_id, meal_id, rating, comment) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iiiis", $user_id, $chef_id, $meal_id, $rating, $comment);

            if ($insert_stmt->execute()) {
                $_SESSION['success_message'] = "Review submitted successfully!";
            } else {
                $_SESSION['error_message'] = "Error submitting review. Please try again.";
            }
            $insert_stmt->close();
        } else {
            $_SESSION['error_message'] = "You have already reviewed this meal.";
        }
        $check_review->close();
    } else {
        $_SESSION['error_message'] = "Invalid meal or unauthorized review.";
    }
    $verify_stmt->close();
}

// Redirect back to the chef's profile
header("Location: ../profile.php?id=" . $chef_id);
exit();
?>