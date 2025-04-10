<?php
require "db.php";

// Handle warning dismissal if POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["warning_id"]) && isset($_SESSION["user_id"])) {
    $warning_id = $_POST["warning_id"];
    $user_id = $_SESSION["user_id"];

    // Verify that the warning belongs to the current user
    $stmt = $conn->prepare("UPDATE chef_warnings SET is_dismissed = TRUE WHERE id = ? AND chef_id = ?");
    $stmt->bind_param("ii", $warning_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the same page
    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

// Display warning notification if user is logged in
if (isset($_SESSION["user_id"])) {
    // Check for undismissed warnings
    $stmt = $conn->prepare("
        SELECT cw.*, u.username as admin_name 
        FROM chef_warnings cw
        JOIN users u ON cw.admin_id = u.id
        WHERE cw.chef_id = ? AND cw.is_dismissed = FALSE
        ORDER BY cw.warning_date DESC
    ");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $warning = $result->fetch_assoc();
        ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Warning from Admin</h4>
            <p><?= htmlspecialchars($warning['reason']); ?></p>
            <hr>
            <p class="mb-0">Issued by: <?= htmlspecialchars($warning['admin_name']); ?> on
                <?= date("m/d/Y H:i", strtotime($warning['warning_date'])); ?>
            </p>
            <form method="POST" class="mt-2">
                <input type="hidden" name="warning_id" value="<?= $warning['id']; ?>">
                <button type="submit" class="btn btn-warning btn-sm">Dismiss Warning</button>
            </form>
        </div>
        <?php
    }
    $stmt->close();
}
?>