<?php
session_start();
require "db.php";

// Check if a meal ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION["error"] = "Invalid meal selection.";
    header("Location: index.php");
    exit;
}

$meal_id = intval($_GET['id']); // Convert to integer to prevent SQL injection

// Retrieve meal details from the database
$stmt = $conn->prepare("SELECT * FROM meals WHERE id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();
$meal = $result->fetch_assoc();
$stmt->close();

if (!$meal) {
    die("Error: Meal not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($meal["title"]); ?> - Meal Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2><?= htmlspecialchars($meal["title"]); ?></h2>

        <!-- Display the Meal Image -->
        <?php if (!empty($meal["image"])): ?>
            <img src="uploads/<?= htmlspecialchars($meal["image"]); ?>" class="img-fluid rounded mb-4" alt="Meal Image"
                style="max-width: 100%; height: auto;">
        <?php else: ?>
            <p class="text-muted">No image available for this meal.</p>
        <?php endif; ?>

        <p><strong>Posted by:</strong> <?= htmlspecialchars($meal["username"]); ?></p>
        <p><strong>Description:</strong> <?= !empty(trim($meal["description"])) ? htmlspecialchars($meal["description"]) : "Not specified"; ?></p>
        <p><strong>Ingredients:</strong> <?= !empty(trim($meal["ingredients"])) ? htmlspecialchars($meal["ingredients"]) : "Not specified"; ?></p>

        <p><strong>Allergies:</strong> 
            <?= (!empty(trim($meal["allergies"])) && $meal["allergies"] !== ", ") 
                ? nl2br(htmlspecialchars($meal["allergies"])) 
                : "None specified"; ?>
        </p>

        <p><strong>Pickup Location:</strong> <?= !empty(trim($meal["pickup_location"])) ? htmlspecialchars($meal["pickup_location"]) : "Not specified"; ?></p>

        <p><strong>Estimated Pickup Time:</strong> 
            <?= (!empty($meal["pickup_time"]) && $meal["pickup_time"] !== "0000-00-00 00:00:00" && $meal["pickup_time"] !== NULL) 
                ? date("F j, Y, g:i A", strtotime($meal["pickup_time"])) 
                : "Not specified"; ?>
        </p>

        <p><small class="text-muted">Posted on <?= !empty($meal["timestamp"]) ? date("F j, Y, g:i A", strtotime($meal["timestamp"])) : "Unknown"; ?></small></p>
        <a href="index.php" class="btn btn-primary">Back to Meals</a>
    </div>
</body>
</html>

<?php
$conn->close();
?>


