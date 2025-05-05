<?php
session_start();
require "db.php";

// Check if a meal ID was provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION["error"] = "Invalid meal selection.";
  header("Location: index.php");
  exit;
}

$meal_id = intval($_GET['id']);

// Get meal details
$stmt = $conn->prepare("SELECT m.*, u.username, m.pickup_location as address FROM meals m JOIN users u ON m.user_id = u.id WHERE m.id = ?");
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

// Prepare pickup coordinates and address
$coords = explode(",", $meal["address"]);
$pickupLat = isset($coords[0]) ? floatval($coords[0]) : 0;
$pickupLng = isset($coords[1]) ? floatval($coords[1]) : 0;

// Get formatted address from coordinates using Nominatim
$formatted_address = "";
if ($pickupLat != 0 && $pickupLng != 0) {
  $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$pickupLat}&lon={$pickupLng}";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'FoodForFamily');
  $response = curl_exec($ch);
  curl_close($ch);

  if ($response) {
    $data = json_decode($response, true);
    if (isset($data['display_name'])) {
      $formatted_address = $data['display_name'];
    }
  }
}
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
  <?php include('includes/navbar.php'); ?>
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
            <p><strong>Pickup Window:</strong><br>
              Start: <?= date("m/d/Y, h:i A", strtotime($meal["pickup_time"])); ?><br>
              End: <?= date("m/d/Y, h:i A", strtotime($meal["pickup_end_time"])); ?>
            </p>
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

              // Add edit and delete buttons for the meal's chef
              if ($_SESSION["user_id"] == $meal["user_id"]): ?>
                <div class="mt-3">
                  <a href="edit_meal.php?id=<?= $meal_id ?>" class="btn btn-primary w-100 mb-2">Edit Meal</a>
                  <form method="POST" action="includes/delete_meal.php"
                    onsubmit="return confirm('Are you sure you want to delete this meal? This action cannot be undone.');">
                    <input type="hidden" name="meal_id" value="<?= $meal_id ?>">
                    <button type="submit" class="btn btn-danger w-100">Delete Meal</button>
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
            <div class="card mb-4">
              <div class="card-body">
                <h5 class="card-title">Pickup Information</h5>
                <p class="card-text">
                  <strong>Location:</strong> <?= htmlspecialchars($formatted_address) ?><br>
                  <strong>Available:</strong> <?= date('F j, Y g:i A', strtotime($meal['pickup_time'])) ?> -
                  <?= date('g:i A', strtotime($meal['pickup_end_time'])) ?>
                </p>
                <div id="map" style="height: 300px;" class="mt-3"></div>
                <?php if (isset($_SESSION["user_lat"]) && isset($_SESSION["user_lng"])): ?>
                  <div id="distance-info" class="mt-2 text-muted"></div>
                <?php endif; ?>
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

  <script>
    <?php if ($canViewPickup): ?>
      // Initialize map
      const map = L.map('map').setView([<?= $pickupLat ?>, <?= $pickupLng ?>], 14);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      // Add marker for pickup location
      const pickupIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      });

      const marker = L.marker([<?= $pickupLat ?>, <?= $pickupLng ?>], { icon: pickupIcon }).addTo(map);
      marker.bindPopup("Pickup Location").openPopup();

      <?php if (isset($_SESSION["user_lat"]) && isset($_SESSION["user_lng"])): ?>
        // Add marker for user's location
        const userIcon = L.icon({
          iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        });

        const userMarker = L.marker([<?= $_SESSION["user_lat"] ?>, <?= $_SESSION["user_lng"] ?>], { icon: userIcon }).addTo(map);
        userMarker.bindPopup("Your Location").openPopup();

        // Add line between user and pickup location
        const line = L.polyline([
          [<?= $_SESSION["user_lat"] ?>, <?= $_SESSION["user_lng"] ?>],
          [<?= $pickupLat ?>, <?= $pickupLng ?>]
        ], {
          color: 'blue',
          weight: 3,
          opacity: 0.7,
          dashArray: '10, 10'
        }).addTo(map);

        // Calculate and display distance
        const distance = calculateDistance(
          <?= $_SESSION["user_lat"] ?>,
          <?= $_SESSION["user_lng"] ?>,
          <?= $pickupLat ?>,
          <?= $pickupLng ?>
        );
        document.getElementById('distance-info').innerHTML = `Distance from your location: ${distance.toFixed(1)} miles`;

        // Function to calculate distance between two points
        function calculateDistance(lat1, lon1, lat2, lon2) {
          const R = 3958.8; // Earth's radius in miles
          const dLat = (lat2 - lat1) * Math.PI / 180;
          const dLon = (lon2 - lon1) * Math.PI / 180;
          const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
          const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
          return R * c;
        }
      <?php endif; ?>
    <?php endif; ?>
  </script>

</body>

</html>
<?php $conn->close(); ?>