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
        <!--placeholder for where posts will go-->
        <div class="position-relative p-5 text-center text-muted bg-body border border-dashed rounded-5 col-12">
            <h1 class="text-body-emphasis">Sorry for the wait!</h1>
            <p class="col-lg-6 mx-auto mb-4">
                We are working diligently to get our Webapp in your hands! Thank you for the patience.
            </p>
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