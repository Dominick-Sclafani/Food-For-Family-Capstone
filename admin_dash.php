<?php
ob_start();
session_start();
require "db.php";

// Ensure only admins can access this page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Handle post deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_meal"])) {
    $meal_id = $_POST["meal_id"];
    $stmt = $conn->prepare("DELETE FROM meals WHERE id = ?");
    $stmt->bind_param("i", $meal_id);
    if ($stmt->execute()) {
        $_SESSION["success"] = "Meal deleted successfully.";
    } else {
        $_SESSION["error"] = "Failed to delete meal.";
    }
    $stmt->close();
    header("Location: admin_dash.php");
    exit;
}

// Fetch pending chefs with their ID documents
$pending_chefs = $conn->query("
    SELECT id, username, id_document 
    FROM users 
    WHERE verification_status = 'pending'
");

// Fetch all meals with chef information
$all_meals = $conn->query("
    SELECT m.*, u.username as chef_username 
    FROM meals m 
    JOIN users u ON m.user_id = u.id 
    ORDER BY m.timestamp DESC
");

// Handle chef approvals/rejections
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["chef_id"])) {
    $chef_id = $_POST["chef_id"];

    if (isset($_POST["approve"])) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        $stmt->execute();
    } elseif (isset($_POST["reject"])) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        $stmt->execute();
    }

    header("Location: admin_dash.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Food For Family - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h2 class="text-center">Admin Dashboard</h2>
        <p class="text-center text-muted">Manage pending chef applications and all posts below.</p>

        <!-- Pending Chef Applications Section -->
        <div class="card shadow-lg p-4 mb-4">
            <h4 class="mb-3">Pending Chef Applications</h4>
            <?php if ($pending_chefs->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Username</th>
                            <th>ID Document</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $pending_chefs->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <?php if (!empty($row['id_document'])): ?>
                                        <a href="uploads/ids/<?= htmlspecialchars($row['id_document']); ?>" target="_blank"
                                            class="btn btn-info btn-sm">
                                            View ID Document
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No ID document uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="chef_id" value="<?= $row['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-success btn-sm">
                                            Approve
                                        </button>
                                        <button type="submit" name="reject" class="btn btn-danger btn-sm">
                                            Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No pending chef applications.</p>
            <?php endif; ?>
        </div>

        <!-- All Posts Section -->
        <div class="card shadow-lg p-4">
            <h4 class="mb-3">All Posts</h4>
            <?php if ($all_meals->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Chef</th>
                            <th>Posted On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($meal = $all_meals->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($meal['title']); ?></td>
                                <td><?= htmlspecialchars($meal['chef_username']); ?></td>
                                <td><?= date("m/d/Y H:i", strtotime($meal['timestamp'])); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="meal_details.php?id=<?= $meal['id']; ?>" class="btn btn-info btn-sm">
                                            View
                                        </a>
                                        <form method="POST" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to delete this meal?');">
                                            <input type="hidden" name="meal_id" value="<?= $meal['id']; ?>">
                                            <button type="submit" name="delete_meal" class="btn btn-danger btn-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-center text-muted">No meals posted yet.</p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>