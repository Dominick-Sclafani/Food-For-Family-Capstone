<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require "db.php";

//  Only allow logged-in chefs or admins
if (!isset($_SESSION["username"]) || !in_array($_SESSION["role"], ["chef", "admin"])) {
    $_SESSION["error"] = "Only approved chefs or admins can post meals.";
    header("Location: index.php");
    exit;
}

// If the user is a chef, check verification
if ($_SESSION["role"] === "chef") {
    $stmt = $conn->prepare("SELECT verification_status FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $stmt->bind_result($verification_status);
    $stmt->fetch();
    $stmt->close();

    if ($verification_status !== "approved") {
        $_SESSION["error"] = "Your chef account is still pending approval.";
        header("Location: index.php");
        exit;
    }
}

//  Process the form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION["user_id"];
    $username = $_SESSION["username"];
    $title = trim($_POST["title"] ?? "Untitled Meal");
    $description = trim($_POST["description"] ?? "No description provided");
    $ingredients = trim($_POST["ingredients"] ?? "Not specified");
    $allergies = isset($_POST["allergens"]) && is_array($_POST["allergens"]) ? implode(", ", $_POST["allergens"]) : "None";
    $raw_address = trim($_POST["pickup_location"] ?? "Not specified");

    // Encode the address for the API
    $encoded_address = urlencode($raw_address);
    $nominatim_url = "https://nominatim.openstreetmap.org/search?q={$encoded_address}&format=json&limit=1";

    // Set user-agent header to avoid being blocked
    $opts = ['http' => ['header' => "User-Agent: FoodForFamilyApp/1.0\r\n"]];
    $context = stream_context_create($opts);

    // Make API request
    $response = file_get_contents($nominatim_url, false, $context);
    $geo_data = json_decode($response, true);

    // Default fallback
    $pickup_location = "0,0";

    // Use coordinates if found
    if (!empty($geo_data) && isset($geo_data[0]['lat'], $geo_data[0]['lon'])) {
        $lat = $geo_data[0]['lat'];
        $lon = $geo_data[0]['lon'];
        $pickup_location = "{$lat},{$lon}";
    } else {
        $_SESSION["error"] = "Could not geocode pickup address. Please enter a more specific address.";
        header("Location: index.php");
        exit;
    }
    $pickup_time = trim($_POST["pickup_time"] ?? date("Y-m-d H:i:s"));
    $price = trim($_POST["price"] ?? "0.00");

    $image_filename = null;

    //  Handle image upload if present
    if (!empty($_FILES["meal_image"]["name"])) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $image_filename = time() . "_" . basename($_FILES["meal_image"]["name"]);
        $target_file = $target_dir . $image_filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (!in_array($imageFileType, $allowed_types)) {
            $_SESSION["error"] = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            header("Location: index.php");
            exit;
        }

        if ($_FILES["meal_image"]["size"] > 5000000) {
            $_SESSION["error"] = "Image size too large (Max: 5MB).";
            header("Location: index.php");
            exit;
        }

        if (!move_uploaded_file($_FILES["meal_image"]["tmp_name"], $target_file)) {
            $_SESSION["error"] = "Failed to upload image.";
            header("Location: index.php");
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO meals (user_id, username, title, description, ingredients, allergies, pickup_location, pickup_time, price, image) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssss", $user_id, $username, $title, $description, $ingredients, $allergies, $pickup_location, $pickup_time, $price, $image_filename);

    if ($stmt->execute()) {
        $_SESSION["success"] = "Meal posted successfully!";
    } else {
        $_SESSION["error"] = "Failed to post meal. Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}
?>