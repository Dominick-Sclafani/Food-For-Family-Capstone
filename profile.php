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

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_own_profile) {
    if (isset($_POST['update_profile'])) {
        $new_username = $_POST['username'];

        // Check if username is already taken
        $check_username = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_username->bind_param("si", $new_username, $profile_id);
        $check_username->execute();
        if ($check_username->get_result()->num_rows > 0) {
            $_SESSION["error"] = "Username already taken.";
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_username, $profile_id);
            if ($update_stmt->execute()) {
                $_SESSION["success"] = "Profile updated successfully!";
                $_SESSION["username"] = $new_username;
                header("Location: profile.php");
                exit;
            } else {
                $_SESSION["error"] = "Error updating profile.";
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify current password
        $check_password = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $check_password->bind_param("i", $profile_id);
        $check_password->execute();
        $check_password->bind_result($hashed_password);
        $check_password->fetch();
        $check_password->close();

        if (password_verify($current_password, $hashed_password)) {
            if ($new_password === $confirm_password) {
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_password->bind_param("si", $hashed_new_password, $profile_id);
                if ($update_password->execute()) {
                    $_SESSION["success"] = "Password changed successfully!";
                    header("Location: profile.php");
                    exit;
                } else {
                    $_SESSION["error"] = "Error changing password.";
                }
            } else {
                $_SESSION["error"] = "New passwords do not match.";
            }
        } else {
            $_SESSION["error"] = "Current password is incorrect.";
        }
    }
}
?>

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

    <?php if ($is_own_profile): ?>
        <!-- Profile Edit Button -->
        <div class="row mb-4">
            <div class="col-12">
                <button class="btn btn-primary w-100" type="button" data-bs-toggle="collapse"
                    data-bs-target="#editProfileForm" aria-expanded="false" aria-controls="editProfileForm">
                    Edit Profile
                </button>
            </div>
        </div>

        <!-- Combined Edit Forms -->
        <div class="collapse mb-4" id="editProfileForm">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Edit Profile</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username"
                                value="<?= htmlspecialchars($db_username); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>

                    <hr class="my-4">

                    <h5 class="card-title">Change Password</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

        <!-- User's Meals -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Posted Meals</h5>
                        <?php
                        $meals_query = $conn->prepare("SELECT * FROM meals WHERE user_id = ? ORDER BY timestamp DESC");
                        $meals_query->bind_param("i", $profile_id);
                        $meals_query->execute();
                        $meals_result = $meals_query->get_result();

                        if ($meals_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Price</th>
                                            <th>Posted On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($meal = $meals_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <a href="meal_details.php?id=<?= $meal['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($meal['title']) ?>
                                                    </a>
                                                </td>
                                                <td>$<?= number_format($meal['price'], 2) ?></td>
                                                <td><?= date("m/d/Y", strtotime($meal['timestamp'])) ?></td>
                                                <td>
                                                    <?php if ($is_own_profile): ?>
                                                        <a href="edit_meal.php?id=<?= $meal['id'] ?>"
                                                            class="btn btn-sm btn-primary">Edit</a>
                                                        <form method="POST" action="includes/delete_meal.php" class="d-inline">
                                                            <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Are you sure you want to delete this meal?')">Delete</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No meals posted yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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

<?php include('includes/footer.php'); ?>