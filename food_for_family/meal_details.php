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

// Retrieve meal details
$stmt = $conn->prepare("SELECT * FROM meals WHERE id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Meal not found.";
    header("Location: index.php");
    exit;
}

$meal = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($meal["title"]); ?> - Meal Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="bg-light">
    <div class="container mt-5">
        <h2><?= htmlspecialchars($meal["title"]); ?></h2>
        <p><strong>Posted by:</strong> <?= htmlspecialchars($meal["username"]); ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($meal["description"]); ?></p>
        <p><strong>Ingredients:</strong> <?= htmlspecialchars($meal["ingredients"]); ?></p>
        <p><strong>Allergies:</strong> <?= htmlspecialchars($meal["allergies"]); ?></p>
        <p><strong>Pickup Location:</strong> <?= htmlspecialchars($meal["pickup_location"]); ?></p>
        <p><small class="text-muted">Posted on <?= $meal["timestamp"]; ?></small></p>
        <a href="index.php" class="btn btn-primary">Back to Meals</a>
    </div>
</body>

</html>
<?php
$conn->close();
?>