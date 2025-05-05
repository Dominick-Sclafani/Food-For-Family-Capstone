<?php include('includes/session_start.php'); ?>
<?php include('includes/header.php'); ?>

<div class="container mt-5">
    <?php include('includes/messages.php'); ?>

    <div class="row">
        <div class="col">
            <?php if (!isset($_SESSION["username"])): ?>
                <?php include('includes/guest_intro.php'); ?>
                <?php include('includes/login_form.php'); ?>
                <?php include('includes/register_form.php'); ?>
            <?php else: ?>
                <?php include('includes/welcome_user.php'); ?>

                <?php if ($verification_status === NULL): ?>
                    <?php include('includes/chef_form.php'); ?>
                <?php elseif ($verification_status === "rejected"): ?>
                    <p class="text-center text-danger fw-bold">
                        Your Home Cook account was rejected. Please contact an admin if you feel this was incorrect.
                    </p>
                <?php elseif (($role === "chef" && $verification_status === "approved") || $role === "admin"): ?>
                    <p class="text-center text-success fw-bold">
                        Your Home Cook account is approved! You can post meals.
                    </p>
                    <?php include('includes/post_meal_form.php'); ?>
                <?php endif; ?>

                <?php
                // Get all meals with user information
                $meals_query = "
                    SELECT m.*, u.username, u.verification_status,
                    (SELECT COUNT(*) FROM purchases WHERE meal_id = m.id) as purchase_count,
                    (SELECT AVG(rating) FROM reviews r WHERE r.chef_id = m.user_id) as avg_rating
                    FROM meals m
                    JOIN users u ON m.user_id = u.id
                    WHERE m.pickup_end_time > CONVERT_TZ(NOW(), 'SYSTEM', '+00:00')
                    AND m.pickup_end_time > m.pickup_time
                    ORDER BY m.timestamp DESC";
                ?>

                <?php include('includes/meals_list.php'); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>