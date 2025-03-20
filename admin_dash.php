<?php
session_start();
require "db.php";

// Ensure only admins can access this page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Fetch pending chefs
$result = $conn->query("SELECT id, username FROM users WHERE verification_status = 'pending'");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Food For Family - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <h2 class="text-center">Admin Dashboard</h2>
        <p class="text-center text-muted">Manage pending chef applications below.</p>

        <div class="card shadow-lg p-4">
            <h4 class="mb-3">Pending Chef Applications</h4>
            <?php if ($result->num_rows > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Username</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="chef_id" value="<?= $row['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-success btn-sm">
                                            ✅ Approve
                                        </button>
                                        <button type="submit" name="reject" class="btn btn-danger btn-sm">
                                            ❌ Reject
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
    </div>

</body>

</html>

<?php
// Handle approvals/rejections
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chef_id = $_POST["chef_id"];

    if (isset($_POST["approve"])) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        $stmt->execute();
    } elseif (isset($_POST["reject"])) {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $chef_id);
        $stmt->execute();
    }

    header("Location: admin_dash.php");
    exit;
}
?>