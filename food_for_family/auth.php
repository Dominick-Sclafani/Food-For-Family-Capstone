<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if ($action == "register") {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION["error"] = "Username already exists. Please choose another.";
            header("Location: index.php"); // Redirect user back to display the error message
            exit;
        }
        $stmt->close();

        // Insert new user with hashed password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION["success"] = "Registration successful! You can now log in.";
        } else {
            $_SESSION["error"] = "Registration failed. Please try again.";
        }
        header("Location: index.php");
        exit;
    }

    if ($action == "login") {
        $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        //verify password and decrypt hash
        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION["username"] = $username;
            $_SESSION["success"] = "Login successful! Welcome, $username.";
        } else {
            $_SESSION["error"] = "Invalid username or password.";
        }
        header("Location: index.php");
        exit;
    }
}
?>