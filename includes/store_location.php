<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lat']) && isset($_POST['lng'])) {
    $_SESSION['user_lat'] = floatval($_POST['lat']);
    $_SESSION['user_lng'] = floatval($_POST['lng']);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>