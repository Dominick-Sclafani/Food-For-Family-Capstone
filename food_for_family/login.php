<?php
session_start();
$conn = new mysqli("localhost", "root", "", "food_for_family");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION["username"] = $username;
        header("Location: welcome.php");
        exit;
    } else {
        echo "Invalid username or password.";
    }
    $stmt->close();
}
?>
