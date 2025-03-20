<?php
session_start();
require "db.php";

// Ensure only admins can access this page
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Fetch pending chefs
$result = $conn->query("SELECT id, username FROM users WHERE verification_status = 'pending'");

echo "<h2>Pending Chef Applications</h2>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<p>{$row['username']}
              <form method='POST'>
                <input type='hidden' name='chef_id' value='{$row['id']}'>
                <button type='submit' name='approve'>Approve</button>
                <button type='submit' name='reject'>Reject</button>
              </form>";

        // Show uploaded ID document if available
        $id_path = "uploads/ids/" . $row['id'] . "_id.jpg"; // Modify if different format
        if (file_exists($id_path)) {
            echo "<br><a href='$id_path' target='_blank'>View ID Document</a>";
        }
        echo "</p>";
    }
} else {
    echo "<p>No pending chefs.</p>";
}

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