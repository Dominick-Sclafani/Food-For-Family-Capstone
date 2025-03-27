<?php
session_start();
require "db.php";

include('includes/navbar.php');


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
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title mb-4"><?= htmlspecialchars($meal["title"]); ?></h2>

        <?php if (!empty($meal["image"])): ?>
          <img src="uploads/<?= htmlspecialchars($meal["image"]); ?>" class="img-fluid rounded mb-4" alt="Meal Image">
        <?php endif; ?>

        <div class="row">
          <div class="col-md-8">
            <p><strong>Posted by:</strong> <a href="profile.php?id=<?= $meal['user_id'] ?>"
                class="text-decoration-none"><?= htmlspecialchars($meal["username"]); ?></a></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($meal["description"]); ?></p>
            <p><strong>Ingredients:</strong> <?= htmlspecialchars($meal["ingredients"]); ?></p>
            <p><strong>Allergens:</strong>
              <?= !empty($meal["allergies"]) ? htmlspecialchars($meal["allergies"]) : "None"; ?></p>
            <p><strong>Estimated Pickup Time:</strong> <?= date("m/d/Y, h:i A", strtotime($meal["pickup_time"])); ?></p>
            <p><strong>Price:</strong> $<?= htmlspecialchars(number_format((float) $meal["price"], 2)); ?></p>
            <p><small class="text-muted">Posted on <?= $meal["timestamp"]; ?></small></p>
          </div>
          <div class="col-md-4">
            <?php
            if (isset($_SESSION["user_id"])) {
              $check_purchase = $conn->prepare("SELECT * FROM purchases WHERE user_id = ? AND meal_id = ?");
              $check_purchase->bind_param("ii", $_SESSION["user_id"], $meal_id);
              $check_purchase->execute();
              $purchase_result = $check_purchase->get_result();

              if ($purchase_result->num_rows === 0): ?>
                <div class="text-center">
                  <form method="POST" action="includes/purchase_meal.php">
                    <input type="hidden" name="meal_id" value="<?= $meal_id ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100">Buy for
                      $<?= number_format($meal["price"], 2) ?></button>
                  </form>
                </div>
              <?php endif;

              $check_purchase->close();
            }
            ?>
          </div>
        </div>

        <!-- Pickup Info -->
        <?php
        $canViewPickup = false;

        if (isset($_SESSION["user_id"])) {
          // Admins can always view pickup info
          if ($_SESSION["role"] === "admin") {
            $canViewPickup = true;
          }
          // Chef who posted the meal can view pickup info
          elseif ($_SESSION["role"] === "chef" && $_SESSION["user_id"] == $meal["user_id"]) {
            $canViewPickup = true;
          }
          // Any user who has purchased the meal can view pickup info
          elseif ($userHasPurchased) {
            $canViewPickup = true;
          }
        }
        ?>

        <div class="mt-4">
          <?php if ($canViewPickup): ?>
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title">Pickup Information</h5>
                <p><strong>Pickup Location:</strong> <?= htmlspecialchars($meal["pickup_location"]); ?></p>
                <h5>Pickup Location Map</h5>
                <div id="map" style="height: 300px;"></div>
                <p id="distance-info" class="mt-3 fw-semibold text-primary"></p>

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
                      document.getElementById("distance-info").innerText = `Distance to pickup: ${distance.toFixed(2)} miles`;
                    }, () => {
                      alert("Unable to access your location.");
                    });
                  }
                </script>
              </div>
            </div>
          <?php else: ?>
            <div class="card shadow-sm">
              <div class="card-body">
                <div class="alert alert-warning">
                  <?php if (!isset($_SESSION["user_id"])): ?>
                    You must be logged in to view the pickup location.
                  <?php else: ?>
                    You must purchase this meal to view its pickup location.
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="mt-4">
          <a href="index.php" class="btn btn-primary">Back to Meals</a>
        </div>

        <!-- Add Reviews Section -->
        <?php if (isset($_SESSION["user_id"])): ?>
          <?php include('includes/review_form.php'); ?>
        <?php endif; ?>

        <?php include('includes/display_reviews.php'); ?>
      </div>
    </div>
  </div>

</body>

</html>
<?php $conn->close(); ?>