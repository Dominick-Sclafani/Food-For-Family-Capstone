<?php
session_start();
require "db.php";
include('includes/header.php');

// Start output buffering
ob_start();

// Ensure only admins can access this page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Initialize warnings array in session if not exists
if (!isset($_SESSION["chef_warnings"])) {
    $_SESSION["chef_warnings"] = array();
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

// Handle chef actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["chef_id"])) {
    $chef_id = $_POST["chef_id"];
    $admin_id = $_SESSION["user_id"];

    if (isset($_POST["send_warning"])) {
        $reason = trim($_POST["warning_reason"]);
        if (!empty($reason)) {
            // Store warning in database
            $stmt = $conn->prepare("INSERT INTO chef_warnings (chef_id, admin_id, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $chef_id, $admin_id, $reason);

            if ($stmt->execute()) {
                $_SESSION["success"] = "Warning sent successfully.";
            } else {
                $_SESSION["error"] = "Failed to send warning.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST["suspend_chef"])) {
        // Delete the user's account and all associated data
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        if ($stmt->execute()) {
            $_SESSION["success"] = "Chef account has been permanently deleted.";
        } else {
            $_SESSION["error"] = "Failed to delete chef account.";
        }
        $stmt->close();
    } elseif (isset($_POST["reinstate_chef"])) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        if ($stmt->execute()) {
            $_SESSION["success"] = "Chef reinstated successfully.";
        } else {
            $_SESSION["error"] = "Failed to reinstate chef.";
        }
        $stmt->close();
    } elseif (isset($_POST["approve"])) {
        // Only update verification status, keep role as chef
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        
        if ($stmt->execute()) {
            $_SESSION["success"] = "Chef approved successfully.";
        } else {
            $_SESSION["error"] = "Failed to approve chef.";
        }
        $stmt->close();
    } elseif (isset($_POST["reject"])) {
        // Only update verification status, keep role as chef
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        
        if ($stmt->execute()) {
            $_SESSION["success"] = "Chef application rejected.";
        } else {
            $_SESSION["error"] = "Failed to reject chef application.";
        }
        $stmt->close();
    }

    // Clear the output buffer before redirecting
    ob_end_clean();
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

// Fetch chefs with concerning review patterns
$concerned_chefs = $conn->query("
    SELECT 
        u.id,
        u.username,
        u.verification_status,
        COUNT(r.id) as total_reviews,
        AVG(r.rating) as avg_rating,
        SUM(CASE WHEN r.rating <= 2 THEN 1 ELSE 0 END) as low_ratings,
        SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star_ratings,
        SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star_ratings,
        SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star_ratings,
        SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star_ratings,
        SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star_ratings,
        MIN(r.timestamp) as first_review,
        MAX(r.timestamp) as last_review,
        (SELECT COUNT(*) FROM chef_warnings WHERE chef_id = u.id AND is_dismissed = FALSE) as warning_count
    FROM users u
    LEFT JOIN reviews r ON r.chef_id = u.id
    WHERE u.role = 'chef' AND u.verification_status != 'suspended'
    GROUP BY u.id, u.username, u.verification_status
    HAVING total_reviews >= 3 AND (avg_rating < 3 OR low_ratings >= 2)
    ORDER BY avg_rating ASC
");

// Fetch recent warnings
$recent_warnings = $conn->query("
    SELECT 
        cw.*,
        u1.username as chef_name,
        u2.username as admin_name
    FROM chef_warnings cw
    JOIN users u1 ON cw.chef_id = u1.id
    JOIN users u2 ON cw.admin_id = u2.id
    ORDER BY cw.warning_date DESC
    LIMIT 10
");

// Fetch recent reviews
$recent_reviews = $conn->query("
    SELECT 
        r.*,
        u1.username as reviewer_name,
        u2.username as chef_name,
        m.title as meal_title
    FROM reviews r
    JOIN users u1 ON r.user_id = u1.id
    JOIN users u2 ON r.chef_id = u2.id
    JOIN meals m ON r.meal_id = m.id
    ORDER BY r.timestamp DESC
    LIMIT 10
");
?>

<div class="container mt-5">
    <h2 class="text-center">Admin Dashboard</h2>
    <p class="text-center text-muted">Manage pending chef applications, review monitoring, and all posts below.</p>

    <!-- Review Monitoring Section -->
    <div class="card shadow-lg p-4 mb-4">
        <h4 class="mb-3">Review Monitoring</h4>

        <!-- Chefs with Concerning Reviews -->
        <div class="mb-4">
            <h5 class="text-danger">Chefs with Concerning Review Patterns</h5>
            <?php if ($concerned_chefs->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Chef</th>
                                <th>Status</th>
                                <th>Warnings</th>
                                <th>Total Reviews</th>
                                <th>Avg Rating</th>
                                <th>Rating Distribution</th>
                                <th>Review Period</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($chef = $concerned_chefs->fetch_assoc()):
                                $warning_count = isset($_SESSION["chef_warnings"][$chef['id']]) ?
                                    count($_SESSION["chef_warnings"][$chef['id']]) : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($chef['username']); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?= $chef['verification_status'] === 'suspended' ? 'danger' : 'warning' ?>">
                                            <?= ucfirst($chef['verification_status']); ?>
                                        </span>
                                    </td>
                                    <td><?= $chef['warning_count']; ?></td>
                                    <td><?= $chef['total_reviews']; ?></td>
                                    <td><?= number_format($chef['avg_rating'], 1); ?>/5</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <span class="text-danger"><?= $chef['one_star_ratings']; ?>★</span>
                                            <span class="text-warning"><?= $chef['two_star_ratings']; ?>★</span>
                                            <span class="text-info"><?= $chef['three_star_ratings']; ?>★</span>
                                            <span class="text-primary"><?= $chef['four_star_ratings']; ?>★</span>
                                            <span class="text-success"><?= $chef['five_star_ratings']; ?>★</span>
                                        </div>
                                    </td>
                                    <td>
                                        <?= date("m/d/Y", strtotime($chef['first_review'])); ?> -
                                        <?= date("m/d/Y", strtotime($chef['last_review'])); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="profile.php?id=<?= $chef['id']; ?>" class="btn btn-info btn-sm">
                                                View Profile
                                            </a>
                                            <?php if ($chef['verification_status'] !== 'suspended'): ?>
                                                <?php if ($chef['warning_count'] < 1): ?>
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                        data-bs-target="#warningModal<?= $chef['id']; ?>"
                                                        <?= $chef['warning_count'] >= 1 ? 'disabled' : '' ?>>
                                                        Send Warning
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($chef['warning_count'] >= 1): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="chef_id" value="<?= $chef['id']; ?>">
                                                        <button type="submit" name="suspend_chef" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Are you sure you want to permanently delete this chef\'s account? This action cannot be undone.');">
                                                            Delete Account
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="chef_id" value="<?= $chef['id']; ?>">
                                                    <button type="submit" name="reinstate_chef" class="btn btn-success btn-sm"
                                                        onclick="return confirm('Are you sure you want to reinstate this chef?');">
                                                        Reinstate
            </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Warning Modal -->
                                        <div class="modal fade" id="warningModal<?= $chef['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Send Warning to
                                                            <?= htmlspecialchars($chef['username']); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="chef_id" value="<?= $chef['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Warning Reason</label>
                                                                <textarea class="form-control" name="warning_reason"
                                                                    rows="3" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="send_warning"
                                                                class="btn btn-warning">Send Warning</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No chefs with concerning review patterns found.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Warnings -->
        <div class="mb-4">
            <h5>Recent Warnings</h5>
            <?php if (!empty($_SESSION["chef_warnings"])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Chef</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent_warnings = array();
                            foreach ($_SESSION["chef_warnings"] as $chef_id => $warnings) {
                                foreach ($warnings as $warning) {
                                    $recent_warnings[] = array(
                                        'chef_id' => $chef_id,
                                        'reason' => $warning['reason'],
                                        'date' => $warning['date']
                                    );
                                }
                            }
                            usort($recent_warnings, function ($a, $b) {
                                return strtotime($b['date']) - strtotime($a['date']);
                            });
                            $recent_warnings = array_slice($recent_warnings, 0, 10);

                            foreach ($recent_warnings as $warning):
                                $chef_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                                $chef_stmt->bind_param("i", $warning['chef_id']);
                                $chef_stmt->execute();
                                $chef_result = $chef_stmt->get_result();
                                $chef = $chef_result->fetch_assoc();
                                $chef_stmt->close();
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($chef['username']); ?></td>
                                    <td><?= htmlspecialchars($warning['reason']); ?></td>
                                    <td><?= date("m/d/Y H:i", strtotime($warning['date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
            </div>
            <?php else: ?>
                <p class="text-center text-muted">No warnings issued yet.</p>
            <?php endif; ?>
        </div>

        <!-- Recent Reviews -->
        <div>
            <h5>Recent Reviews</h5>
            <?php if ($recent_reviews->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Reviewer</th>
                                <th>Chef</th>
                                <th>Meal</th>
                                <th>Rating</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($review = $recent_reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($review['reviewer_name']); ?></td>
                                    <td><?= htmlspecialchars($review['chef_name']); ?></td>
                                    <td><?= htmlspecialchars($review['meal_title']); ?></td>
                                    <td><?= $review['rating']; ?>/5</td>
                                    <td><?= date("m/d/Y H:i", strtotime($review['timestamp'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">No reviews found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Chef Applications Section -->
    <div class="card shadow-lg p-4 mb-4">
            <h4 class="mb-3">Pending Chef Applications</h4>
        <?php if ($pending_chefs->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                <thead class="table-light">
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
                                <form method="POST" action="admin_dash.php" class="d-inline">
                                        <input type="hidden" name="chef_id" value="<?= $row['id']; ?>">
                                    <button type="submit" name="approve" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to approve this chef?');">
                                            Approve
                                        </button>
                                    <button type="submit" name="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this chef?');">
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
                <thead class="table-light">
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

<?php include('includes/footer.php'); ?>