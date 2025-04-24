<?php
session_start();
require "db.php";

include('includes/header.php');

// Get the user ID from URL parameter, or use logged-in user's ID
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

// Fetch user data from database
$stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$stmt->bind_result($db_username, $role);
$stmt->fetch();
$stmt->close();

// If no user found, redirect to home
if (!$db_username) {
    $_SESSION["error"] = "User not found.";
    header("Location: index.php");
    exit;
}

// Check if this is the user's own profile
$is_own_profile = isset($_SESSION["user_id"]) && $_SESSION["user_id"] == $profile_id;

// If not logged in and trying to view someone else's profile, redirect to login
if (!isset($_SESSION["user_id"]) && !$is_own_profile) {
    $_SESSION["error"] = "Please log in to view this profile.";
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_own_profile ? "My Profile" : htmlspecialchars($db_username) . "'s Profile" ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="bg-light">
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <!-- Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center">
                    <div class="profile-image me-4">
                        <img src="uploads/default-profile.png" alt="Profile" class="rounded-circle" width="120"
                            height="120">
                    </div>
                    <div class="profile-info">
                        <h1 class="h3 mb-2"><?= htmlspecialchars($db_username); ?></h1>
                        <span class="badge bg-primary"><?= htmlspecialchars($role); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($role === "chef"): ?>
            <!-- Chef Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <?php
                    // Get number of meals posted
                    $meals_stmt = $conn->prepare("SELECT COUNT(*) as total_meals FROM meals WHERE user_id = ?");
                    $meals_stmt->bind_param("i", $profile_id);
                    $meals_stmt->execute();
                    $meals_result = $meals_stmt->get_result();
                    $meals_data = $meals_result->fetch_assoc();

                    // Get average rating
                    $rating_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE chef_id = ?");
                    $rating_stmt->bind_param("i", $profile_id);
                    $rating_stmt->execute();
                    $rating_result = $rating_stmt->get_result();
                    $rating_data = $rating_result->fetch_assoc();
                    ?>
                    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm">
                        <div>
                            <h6 class="mb-0 text-muted">Meals Posted</h6>
                            <h3 class="mb-0"><?= $meals_data['total_meals'] ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-utensils fa-2x"></i>
                        </div>
                    </div>
                </div>
                <?php if ($rating_data['total_reviews'] > 0): ?>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm">
                            <div>
                                <h6 class="mb-0 text-muted">Average Rating</h6>
                                <h3 class="mb-0"><?= round($rating_data['avg_rating'], 1) ?>/5</h3>
                            </div>
                            <div class="text-warning">
                                <i class="fas fa-star fa-2x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm">
                            <div>
                                <h6 class="mb-0 text-muted">Total Reviews</h6>
                                <h3 class="mb-0"><?= $rating_data['total_reviews'] ?></h3>
                            </div>
                            <div class="text-success">
                                <i class="fas fa-comments fa-2x"></i>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reviews Section -->
            <div class="row">
                <div class="col-12">
                    <div class="bg-white rounded-3 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="mb-0">Reviews</h4>
                            <?php if (!$is_own_profile): ?>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    Write a Review
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Set variables needed for review components
                        $chef_id = $profile_id;
                        $chef_username = $db_username;

                        // Include review components
                        include 'includes/display_reviews.php';
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <?php if (!$is_own_profile): ?>
        <div class="modal fade" id="reviewModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Write a Review for <?= htmlspecialchars($db_username) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php include 'includes/review_form.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>

</html>