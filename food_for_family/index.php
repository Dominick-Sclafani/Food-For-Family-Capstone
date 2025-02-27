<?php
//sessions store user data across pages like a cookie (but not) until the browser is closed
session_start(); //Homepage file
include('db.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food For Family - Homepage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!--bootstrap-->
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!--jQuery for AJAX -->
</head>

<body class="bg-light">
    <div class="container mt-5">
        <?php if (!isset($_SESSION["username"])): ?> <!--checks if user is logged in -->
            <div id="auth-container">
                <h2 class="text-center">Login or Register</h2>
                <div class="btn-group d-flex mb-3">
                    <button class="btn btn-primary w-50" onclick="showForm('login')">Login</button>
                    <button class="btn btn-secondary w-50" onclick="showForm('register')">Register</button>
                </div>
                <div id="form-container">
                    <!-- Forms will fill this area -->
                </div>
            </div>
        <?php else: ?>
            <!--display the logged in user-->
            <div class="text-center">
                <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]); ?>!</h1>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        <?php endif; ?>
    </div>
    <script src="script.js"></script>
</body>

</html>

<?php
$conn->close(); //close db connection

?>