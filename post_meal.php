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
    $pickup_end_time = trim($_POST["pickup_end_time"] ?? date("Y-m-d H:i:s"));
    $price = trim($_POST["price"] ?? "0.00");

    // Validate pickup times
    if (strtotime($pickup_end_time) <= strtotime($pickup_time)) {
        $_SESSION['error'] = "End time must be after start time";
        header("Location: post_meal.php");
        exit();
    }

    $image = null;

    // Handle image upload
    if (isset($_FILES['meal_image']) && $_FILES['meal_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['meal_image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;

            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (move_uploaded_file($_FILES['meal_image']['tmp_name'], $upload_path)) {
                $image = $new_filename; // Store only the filename
            } else {
                $_SESSION['error'] = "Failed to upload image. Please try again.";
                header("Location: post_meal.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Please upload a JPG, JPEG, PNG, or GIF image.";
            header("Location: post_meal.php");
            exit();
        }
    }

    // Insert meal into database
    $stmt = $conn->prepare("INSERT INTO meals (user_id, title, description, ingredients, allergies, pickup_location, pickup_time, pickup_end_time, image, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssd", $user_id, $title, $description, $ingredients, $allergies, $pickup_location, $pickup_time, $pickup_end_time, $image, $price);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Meal posted successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Error posting meal: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}
?>