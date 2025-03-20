<?php
session_start();
require "db.php";

if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION["username"];

// Fetch user data from database
$stmt = $conn->prepare("SELECT username, role FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($db_username, $role);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <h2 class="text-center">My Profile</h2>
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h4 class="card-title"><?= htmlspecialchars($db_username); ?></h4>
                <p class="card-text"><strong>Role:</strong> <?= htmlspecialchars($role); ?></p>
                <a href="index.php" class="btn btn-primary">Back to Home</a>
            </div>
        </div>
    </div>

</body>

</html>