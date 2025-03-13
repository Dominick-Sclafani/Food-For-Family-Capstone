<?php
session_start(); // Start session to track user login state
include('db.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food For Family - Homepage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

    <div class="container mt-5">
        <!-- Display Error / Success Messages -->
        <?php if (isset($_SESSION["error"])): ?>
            <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                <?= htmlspecialchars($_SESSION["error"]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION["error"]); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION["success"])): ?>
            <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
                <?= htmlspecialchars($_SESSION["success"]); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION["success"]); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col">
                <?php if (!isset($_SESSION["username"])): ?>
                    <!-- Show Buttons to Display Forms -->
                    <div class="text-center">
                        <h2>Welcome to Food For Family!</h2>
                        <p>Join us to find and share home-cooked meals.</p>
                        <button class="btn btn-primary" onclick="toggleForm('login')">Login</button>
                        <button class="btn btn-secondary" onclick="toggleForm('register')">Register</button>
                    </div>

                    <!--  Login Form (Initially Hidden) -->
                    <div id="login-form-container" class="form-container mt-3" style="display: none;">
                        <h2>Login</h2>
                        <form id="login-form" method="POST" action="auth.php">
                            <input type="hidden" name="action" value="login">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Login</button>
                        </form>
                    </div>

                    <!--  Registration Form (Initially Hidden) -->
                    <div id="register-form-container" class="form-container mt-3" style="display: none;">
                        <h2>Register</h2>
                        <form id="register-form" method="POST" action="auth.php">
                            <input type="hidden" name="action" value="register">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <!--hidden to default to regular user and prevent js errors-->
                            <input type="hidden" name="role" value="regular">

                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                    </div>

                <?php else: ?>
                    <!--  Show Welcome Message for Logged-in Users -->
                    <div class="container text-center">
                        <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]); ?>!</h1>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>

                    <!--  Chef Registration Button for Regular Users -->
                    <?php if ($_SESSION["role"] === "regular"): ?>
                        <div class="container mt-4 text-center">
                            <h2>Want to Become a Chef?</h2>
                            <p>Click the button below to apply as a chef and start posting meals.</p>
                            <form method="POST" action="chef_reg.php">
                                <button type="submit" class="btn btn-warning">Become a Chef</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <!-- Meal Posting Section (Only for registered chefs) -->
                    <?php if ($_SESSION["role"] === "chef"): ?>
                        <div class="container mt-4">
                            <h2>Post a Meal</h2>
                            <form id="meal-form" method="POST" action="post_meal.php">
                                <div class="mb-3">
                                    <label class="form-label">Meal Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ingredients</label>
                                    <textarea class="form-control" name="ingredients" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Allergies</label>
                                    <input type="text" class="form-control" name="allergies" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Pickup Location</label>
                                    <input type="text" class="form-control" name="pickup_location" required>
                                </div>
                                <button type="submit" class="btn btn-success">Post Meal</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleForm(type) {
            document.getElementById("login-form-container").style.display = (type === "login") ? "block" : "none";
            document.getElementById("register-form-container").style.display = (type === "register") ? "block" : "none";
        }
    </script>

</body>

</html>

<?php
$conn->close();
?>