<?php include('includes/session_start.php'); ?>
<?php include('includes/header.php'); ?>
<?php include('includes/navbar.php'); ?>

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

                <?php include('includes/meals_list.php'); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
