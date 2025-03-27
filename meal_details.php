<?php
session_start();
require "db.php";

// Check if a meal ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION["error"] = "Invalid meal selection.";
  header("Location: index.php");
  exit;
}

$meal_id = intval(value: $_GET['id']);

// Get meal details
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

// Check if user purchased the meal
$userHasPurchased = false;
if (isset($_SESSION["user_id"])) {
  $checkPurchase = $conn->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND meal_id = ?");
  $checkPurchase->bind_param("ii", $_SESSION["user_id"], $meal_id);
  $checkPurchase->execute();
  $checkPurchase->store_result();
  $userHasPurchased = $checkPurchase->num_rows > 0;
  $checkPurchase->close();
}

// Prepare pickup coordinates
$coords = explode(",", $meal["pickup_location"]);
$pickupLat = isset($coords[0]) ? floatval($coords[0]) : 0;
$pickupLng = isset($coords[1]) ? floatval($coords[1]) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Meal Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>

<body class="bg-light">
  <div class="container mt-5">
    <h2><?= htmlspecialchars($meal["title"]); ?></h2>

    <?php if (!empty($meal["image"])): ?>
      <img src="uploads/<?= htmlspecialchars($meal["image"]); ?>" class="img-fluid rounded mb-4" alt="Meal Image">
    <?php endif; ?>

    <p><strong>Posted by:</strong> <?= htmlspecialchars($meal["username"]); ?></p>
    <p><strong>Description:</strong> <?= htmlspecialchars($meal["description"]); ?></p>
    <p><strong>Ingredients:</strong> <?= htmlspecialchars($meal["ingredients"]); ?></p>
    <p><strong>Allergens:</strong> <?= !empty($meal["allergies"]) ? htmlspecialchars($meal["allergies"]) : "None"; ?>
    </p>
    <p><strong>Estimated Pickup Time:</strong> <?= date("m/d/Y, h:i A", strtotime($meal["pickup_time"])); ?></p>
    <p><strong>Price:</strong> $<?= htmlspecialchars(number_format((float) $meal["price"], 2)); ?></p>
    <p><small class="text-muted">Posted on <?= $meal["timestamp"]; ?></small></p>

    <a href="index.php" class="btn btn-primary mb-4">Back to Meals</a>

    <!-- Buy Button (Only for regular users who haven't purchased yet) -->
    <?php
    if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "regular") {
      $check_purchase = $conn->prepare("SELECT * FROM purchases WHERE user_id = ? AND meal_id = ?");
      $check_purchase->bind_param("ii", $_SESSION["user_id"], $meal_id);
      $check_purchase->execute();
      $purchase_result = $check_purchase->get_result();

      if ($purchase_result->num_rows === 0): ?>
        <form method="POST" action="includes/purchase_meal.php">
          <input type="hidden" name="meal_id" value="<?= $meal_id ?>">
          <button type="submit" class="btn btn-success mb-3">Buy for $<?= number_format($meal["price"], 2) ?></button>
        </form>
      <?php endif;

      $check_purchase->close();
    }
    ?>

    <!-- Pickup Info -->
    <?php
    $canViewPickup = false;

    if (isset($_SESSION["user_id"])) {
      // Admins and Chefs can always view pickup info
      if ($_SESSION["role"] === "admin" || $_SESSION["role"] === "chef") {
        $canViewPickup = true;
      } elseif ($_SESSION["role"] === "regular" && $userHasPurchased) {
        $canViewPickup = true;
      }
    }
    ?>

    <?php if ($canViewPickup): ?>
      <p><strong>Pickup Location:</strong> <?= htmlspecialchars($meal["pickup_location"]); ?></p>
      <h5>Pickup Location Map</h5>
      <div id="map" style="height: 300px;"></div>

      <script>
        const pickupLat = <?= $pickupLat ?>;
        const pickupLng = <?= $pickupLng ?>;
        const map = L.map('map').setView([pickupLat, pickupLng], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19
        }).addTo(map);

        L.marker([pickupLat, pickupLng]).addTo(map).bindPopup("Pickup Location").openPopup();

        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(position => {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;

            L.marker([userLat, userLng]).addTo(map).bindPopup("Your Location");
            L.polyline([[userLat, userLng], [pickupLat, pickupLng]], { color: 'blue' }).addTo(map);
            const distance = map.distance([userLat, userLng], [pickupLat, pickupLng]) / 1609.34;
            alert(`Distance to pickup: ${distance.toFixed(2)} miles`);
          }, () => {
            alert("Unable to access your location.");
          });
        }
      </script>
    <?php else: ?>
      <div class="alert alert-warning">
        You must purchase this meal to view its pickup location.
      </div>
    <?php endif; ?>

  </div>
</body>

</html>
<?php $conn->close(); ?>