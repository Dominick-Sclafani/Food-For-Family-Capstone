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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Food For Family</a>

            <!-- Toggle Button for Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION["username"])): ?>
                        <!-- Profile Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="uploads/default-profile.png" alt="Profile" width="30" height="30"
                                    class="rounded-circle">
                                <?= htmlspecialchars($_SESSION["username"]); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <?php if ($_SESSION["role"] === "admin"): ?>
                                    <li><a class="dropdown-item" href="admin_dash.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

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
                                <small id="password-requirements" class="form-text text-muted">
                                    Password must:
                                    <ul>
                                        <li id="length" class="text-danger">Be at least 6 characters long</li>
                                        <li id="uppercase" class="text-danger">Contain at least one uppercase letter</li>
                                        <li id="lowercase" class="text-danger">Contain at least one lowercase letter</li>
                                        <li id="number" class="text-danger">Contain at least one number</li>
                                    </ul>
                                </small>
                                <small id="password-error" class="text-danger" style="display: none;">Password does not meet
                                    the requirements.</small>
                            </div>
                            <input type="hidden" name="role" value="regular">
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>
                    </div>
                <?php else: ?>


                    <!-- Show Welcome Message for Logged-in Users -->
                    <div class="container text-center">
                        <h1>Welcome, <?= htmlspecialchars($_SESSION["username"]); ?>!</h1>
                    </div>


                    <!-- Chef Registration Form -->
                    <?php


                    // If the user has not applied (NULL), show the application form
                    if ($verification_status === NULL): ?>
                        <div class="container mt-4 text-center">
                            <h2>Want to post your meals and be a "Home Cook" with us?</h2>
                            <p>Complete the form below to apply. <small>You must be at least 23 years old to apply.</small></p>

                            <form method="POST" action="chef_reg.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="dob" id="dob" required>
                                    <small class="text-danger" id="dob-warning" style="display:none;">You must be at least 23
                                        years old.</small>
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
                    ?>
                    <?php if (isset($verification_status) === "rejected"): ?>
                        <p class="text-center"
                            stlye="color: red; font-weigt:bold;'> Your Home Cook account was rejected. Please contact an admin if you feel that our decision was incorrerct">
                        <?php endif ?>

                        <?php if (isset($role) && $role === "chef" && $verification_status === "approved" || $role === "admin"): ?>
                        <p class='text-center' style='color: green; font-weight: bold;'>Your Home Cook account is approved! You
                            can
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
                                    <label class="form-label">Common Allergens</label>
                                    <div class="d-flex flex-wrap">
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Peanuts"
                                                id="peanuts">
                                            <label class="form-check-label" for="peanuts">Peanuts</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Tree Nuts"
                                                id="tree-nuts">
                                            <label class="form-check-label" for="tree-nuts">Tree Nuts</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Dairy"
                                                id="dairy">
                                            <label class="form-check-label" for="dairy">Dairy</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Eggs"
                                                id="eggs">
                                            <label class="form-check-label" for="eggs">Eggs</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Shellfish"
                                                id="shellfish">
                                            <label class="form-check-label" for="shellfish">Shellfish</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Fish"
                                                id="fish">
                                            <label class="form-check-label" for="fish">Fish</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Soy"
                                                id="soy">
                                            <label class="form-check-label" for="soy">Soy</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Wheat"
                                                id="wheat">
                                            <label class="form-check-label" for="wheat">Wheat</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Sesame"
                                                id="sesame">
                                            <label class="form-check-label" for="sesame">Sesame</label>
                                        </div>
                                        <div class="form-check m-2">
                                            <input class="form-check-input" type="checkbox" name="allergens[]" value="Gluten"
                                                id="gluten">
                                            <label class="form-check-label" for="gluten">Gluten</label>
                                        </div>
                                    </div>
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