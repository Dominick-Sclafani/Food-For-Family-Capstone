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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">
    <div class="container mt-5">
        <div class="row">
            <div class="col">
                <?php if (!isset($_SESSION["username"])): ?> <!--checks if user is logged in -->
                    <div id="auth-container">
                        <h2 class="text-center">Login or Register with us!</h2>
                        <div class="btn-group d-flex mb-3">
                            <button class="btn btn-primary w-50" onclick="showForm('login')">Login</button>
                            <button class="btn btn-secondary w-50" onclick="showForm('register')">Register</button>
                        </div>
                        <div id="form-container">
                            <!-- Forms will fill this area -->
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!--display the logged in user-->
                <div class=" container text-center ">
                    <div class="row">
                        <div class="col">
                            <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]); ?>!</h1>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION["username"])): ?>
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

    <!--Proper error handling putting in through the html-->
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
    </div>

    <script src="script.js"></script>
</body>

</html>

<?php
$conn->close(); //close db connection

?>