<?php
// Get user's location if logged in
$userLat = null;
$userLng = null;

if (isset($_SESSION["user_id"])) {
    // Get user's location from session or request it
    if (isset($_SESSION["user_lat"]) && isset($_SESSION["user_lng"])) {
        $userLat = $_SESSION["user_lat"];
        $userLng = $_SESSION["user_lng"];
    }
}
?>

<div class="container mt-5">
    <h2 class="text-center">Available Meals</h2>

    <div class="row">
        <?php
        // Base query to get all meals
        $query = "
            SELECT m.id, m.title, m.image, m.timestamp, m.user_id, m.pickup_location, u.username 
            FROM meals m 
            JOIN users u ON m.user_id = u.id 
        ";

        // If we have user's location, calculate distances
        if ($userLat !== null && $userLng !== null) {
            // Add distance-based ordering
            $query .= " ORDER BY 
                CASE 
                    WHEN pickup_location LIKE '%,%' THEN 
                        (6371 * acos(
                            cos(radians(?)) * cos(radians(SUBSTRING_INDEX(pickup_location, ',', 1))) * 
                            cos(radians(SUBSTRING_INDEX(pickup_location, ',', -1)) - radians(?)) + 
                            sin(radians(?)) * sin(radians(SUBSTRING_INDEX(pickup_location, ',', 1)))
                        )) * 0.621371
                    ELSE 999999
                END ASC
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("ddd", $userLat, $userLng, $userLat);
        } else {
            $query .= " ORDER BY m.timestamp DESC";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()):
                $distance = null;
                if ($userLat !== null && $userLng !== null && strpos($row["pickup_location"], ',') !== false) {
                    $coords = explode(",", $row["pickup_location"]);
                    $mealLat = floatval($coords[0]);
                    $mealLng = floatval($coords[1]);

                    // Calculate distance in miles
                    $distance = calculateDistance($userLat, $userLng, $mealLat, $mealLng);
                }
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card shadow-lg">
                        <?php if (!empty($row["image"])): ?>
                            <img src="uploads/<?= htmlspecialchars($row["image"]); ?>" class="card-img-top" alt="Meal Image">
                        <?php else: ?>
                            <img src="uploads/default-placeholder.png" class="card-img-top" alt="No Image Available">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="meal_details.php?id=<?= $row['id']; ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($row["title"]); ?>
                                </a>
                            </h5>
                            <p class="card-text">
                                <strong>Posted by:</strong>
                                <a href="profile.php?id=<?= $row['user_id']; ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($row["username"]); ?>
                                </a>
                            </p>
                            <?php if ($distance !== null): ?>
                                <p class="card-text">
                                    <strong>Distance:</strong>
                                    <span class="text-success"><?= number_format($distance, 1) ?> miles away</span>
                                </p>
                            <?php endif; ?>
                            <p class="card-text"><small class="text-muted">Posted on <?= $row["timestamp"]; ?></small></p>
                        </div>
                    </div>
                </div>
            <?php endwhile;
        else: ?>
            <p class="text-center text-muted">No meals available yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    // Automatically request location access when the page loads
    if (navigator.geolocation && !<?= isset($_SESSION["user_lat"]) ? 'true' : 'false' ?>) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                // Store location in session via AJAX
                fetch('includes/store_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `lat=${position.coords.latitude}&lng=${position.coords.longitude}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            },
            function (error) {
                console.log('Location access denied or not available');
            }
        );
    }
</script>

<?php
// Helper function to calculate distance between two points
function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    return $miles;
}
?>