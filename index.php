<?php
session_start(); // Start session to track user login state
include('db.php');
?>
<?php
// Check the current role and verification status for ALL logged-in users
if (isset($_SESSION["user_id"])) {
    $stmt = $conn->prepare("SELECT role, verification_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $stmt->bind_result($role, $verification_status);
    $stmt->fetch();
    $stmt->close();
}
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
                            <input type="hidden" name="role" value="regular">
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Show Welcome Message for Logged-in Users -->
                    <div class="container text-center">
                        <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]); ?>!</h1>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>


                    <!-- Chef Registration Form -->
                    <?php
                    if (isset($_SESSION["role"]) && $_SESSION["role"] === "regular") {
                        // Check the current verification status
                        $stmt = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
                        $stmt->bind_param("i", $_SESSION["user_id"]);
                        $stmt->execute();
                        $stmt->bind_result($verification_status);
                        $stmt->fetch();
                        $stmt->close();

                        // If the user has not applied (NULL), show the application form
                        if ($verification_status === NULL): ?>
                            <div class="container mt-4 text-center">
                                <h2>Want to Become a Chef?</h2>
                                <p>Complete the form below to apply. You must be at least 23 years old.</p>

                                <form method="POST" action="chef_reg.php" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" name="full_name" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Age</label>
                                        <input type="number" class="form-control" name="age" min="23" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Why do you want to become a chef?</label>
                                        <textarea class="form-control" name="reason" required></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Upload an ID with Date Of birth</label>
                                        <input type="file" class="form-control" name="id_document" accept=".jpg,.jpeg,.png,.pdf"
                                            required>
                                    </div>

                                    <button type="submit" class="btn btn-warning">Submit Application</button>
                                </form>
                            </div>
                        <?php endif;
                    } ?>


                    <?php if (isset($role) && $role === "chef" && $verification_status === "approved"): ?>
                        <p class='text-center' style='color: green; font-weight: bold;'>Your chef account is approved! You can
                            post meals.</p>

                        <!-- Meal Posting Form -->
                        <div class="container mt-4">
                            <h2>Post a Meal</h2>
                            <form id="meal-form" method="POST" action="post_meal.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Meal Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Estimated Time for Pickup</label>
                                    <input type="datetime-local" class="form-control" name="pickup_time" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" required></textarea>
                                </div>

                                <!-- Allergen Dropdown -->
                                <div class="mb-3">
                                    <label class="form-label">Common Allergies</label>
                                    <select id="allergies" class="form-control" name="allergies[]" multiple="multiple">
                                        <option value="Peanuts">Peanuts</option>
                                        <option value="Tree Nuts">Tree Nuts</option>
                                        <option value="Dairy">Dairy</option>
                                        <option value="Eggs">Eggs</option>
                                        <option value="Shellfish">Shellfish</option>
                                        <option value="Fish">Fish</option>
                                        <option value="Soy">Soy</option>
                                        <option value="Wheat">Wheat</option>
                                        <option value="Sesame">Sesame</option>
                                        <option value="Gluten">Gluten</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Pickup Location</label>
                                    <input type="text" class="form-control" name="pickup_location" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Upload Meal Image</label>
                                    <input type="file" class="form-control" name="meal_image" accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-success">Post Meal</button>
                            </form>
                        </div>
                    <?php endif; ?>


                    <!-- Available Meals Section (Only for Logged-in Users) -->
                    <div class="container mt-5">
                        <h2 class="text-center">Available Meals</h2>
                        <div class="row">
                            <?php
                            $result = $conn->query("SELECT id, title, username, image, timestamp FROM meals ORDER BY timestamp DESC");

                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()): ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card shadow-lg">
                                            <?php if (!empty($row["image"])): ?>
                                                <img src="uploads/<?= htmlspecialchars($row["image"]); ?>" class="card-img-top"
                                                    alt="Meal Image">
                                            <?php else: ?>
                                                <img src="uploads/default-placeholder.png" class="card-img-top"
                                                    alt="No Image Available">
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="meal_details.php?id=<?= $row['id']; ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($row["title"]); ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text"><strong>Posted by:</strong>
                                                    <?= htmlspecialchars($row["username"]); ?></p>
                                                <p class="card-text"><small class="text-muted">Posted on
                                                        <?= $row["timestamp"]; ?></small></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile;
                            else: ?>
                                <p class="text-center text-muted">No meals available yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>

<?php $conn->close(); ?>