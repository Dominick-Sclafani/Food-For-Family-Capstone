<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // Ensure 'role' key exists in POST request
    $role = isset($_POST["role"]) && $_POST["role"] === "chef" ? "chef" : "regular";

    if ($action == "register") {
        // Password Requirements
        if (
            strlen($password) < 6 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/\d/', $password)
        ) {
            $_SESSION["error"] = "Password must be at least 6 characters long and include at least one uppercase letter, one lowercase letter, and one number.";
            header("Location: index.php");
            exit;
        }

        // Check if username already exists
        $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $_SESSION["error"] = "Username already exists. Please choose another.";
            header("Location: index.php");
            exit;
        }
        $stmt->close();

        // Insert new user with hashed password and role
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            $_SESSION["success"] = "Registration successful! You can now log in.";
        } else {
            $_SESSION["error"] = "Registration failed. Please try again.";
        }
        $stmt->close();
        header("Location: index.php");
        exit;
    }

    if ($action == "login") {
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        // Fix: Ensure correct number of bind variables
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION["username"] = $username;
            $_SESSION["user_id"] = $user_id;
            $_SESSION["role"] = $role;
            $_SESSION["success"] = "Login successful! Welcome, $username.";
        } else {
            $_SESSION["error"] = "Invalid username or password.";
        }
        $stmt->close();
        header("Location: index.php");
        exit;
    }
}
?>