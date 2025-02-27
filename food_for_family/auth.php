<?php

//handling user authentication
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]); //remove white space in username and pw

    if ($action == "register") { //if there is a new user, use php built in hash and bind the username and password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
        $stmt->close();
    }

    if ($action == "login") {//if existing user
        $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        //password verification
        if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
            $_SESSION["username"] = $username;
            echo "success";
        } else {
            echo "invalid";
        }
        $stmt->close();
    }
}
?>