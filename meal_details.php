<?php
session_start();
require "db.php";

// Check if a meal ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION["error"] = "Invalid meal selection.";
    header("Location: index.php");
    exit;
}

$meal_id = intval($_GET['id']); // Convert to integer to prevent SQL injection

// Retrieve meal details
$stmt = $conn->prepare("SELECT * FROM meals WHERE id = ?");
$stmt->bind_param("i", $meal_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Meal not found.";
    header("Location: index.php");
    exit;
}

$meal = $result->fetch_assoc();
$stmt->close();

// Prepare coordinates
$coords = explode(",", $meal["pickup_location"]);
$pickupLat = isset($coords[0]) ? floatval($coords[0]) : 0;
$pickupLng = isset($coords[1]) ? floatval($coords[1]) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Food For Family - Meal Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>

<body class="bg-light">
<div class="container mt-5">
  <h2><?= htmlspecialchars($meal["title"]); ?></h2>

  <?php if (!empty($meal["image"])): ?>
    <img src="uploads/<?= htmlspecialchars($meal["image"]); ?>" class="img-fluid rounded mb-4" alt="Meal Image" style="max-width: 100%; height: auto;">
  <?php else: ?>
    <p class="text-muted">No image available for this meal.</p>
  <?php endif; ?>

  <p><strong>Posted by:</strong> <?= htmlspecialchars($meal["username"]); ?></p>
  <p><strong>Description:</strong> <?= htmlspecialchars($meal["description"]); ?></p>
  <p><strong>Ingredients:</strong> <?= htmlspecialchars($meal["ingredients"]); ?></p>
  <p><strong>Allergens:</strong> <?= !empty($meal["allergies"]) ? htmlspecialchars($meal["allergies"]) : "None"; ?></p>
  <p><strong>Estimated Pickup Time:</strong> <?= date("m/d/Y, h:i A", strtotime($meal["pickup_time"])); ?></p>
  <p><strong>Pickup Location:</strong> <?= htmlspecialchars($meal["pickup_location"]); ?></p>
  <p><strong>Price:</strong> $<?= htmlspecialchars(number_format((float)$meal["price"], 2)); ?></p>
  <p><small class="text-muted">Posted on <?= $meal["timestamp"]; ?></small></p>
  <a href="index.php" class="btn btn-primary mb-4">Back to Meals</a>

  <!-- Map -->
  <h5>Pickup Location Map</h5>
  <div id="map" style="height: 300px;"></div>
</div>

<script>
const pickupLat = <?= $pickupLat ?>;
const pickupLng = <?= $pickupLng ?>;

const map = L.map('map').setView([pickupLat, pickupLng], 14);

// Add OpenStreetMap tiles
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19
}).addTo(map);

// Mark pickup location
L.marker([pickupLat, pickupLng]).addTo(map).bindPopup("Pickup Location").openPopup();

// Get and mark user location
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(position => {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;

    // Add user marker
    L.marker([userLat, userLng]).addTo(map).bindPopup("Your Location").openPopup();

    // Draw line and show distance
    L.polyline([[userLat, userLng], [pickupLat, pickupLng]], { color: 'blue' }).addTo(map);
    const distance = map.distance([userLat, userLng], [pickupLat, pickupLng]) / 1609.34; // meters to miles

    // Show alert or display somewhere
    alert(`Distance to pickup: ${distance.toFixed(2)} miles`);
  }, () => {
    alert("Unable to access your location.");
  });
} else {
  alert("Geolocation is not supported by this browser.");
}
</script>
</body>
</html>

<?php $conn->close(); ?>
