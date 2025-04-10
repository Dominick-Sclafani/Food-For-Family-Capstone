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

// Get sort option from URL parameter
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'recent';
?>

<div class="container mt-5">
    <h2 class="text-center">Available Meals</h2>

    <!-- Sorting Dropdown -->
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form method="GET" class="d-flex gap-2">
                <select name="sort" class="form-select" onchange="this.form.submit()">
                    <option value="recent" <?= $sort_option === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                    <option value="distance" <?= $sort_option === 'distance' ? 'selected' : '' ?>>Distance (Closest First)
                    </option>
                    <option value="rating" <?= $sort_option === 'rating' ? 'selected' : '' ?>>Highest Rated Chefs</option>
                    <option value="price_low" <?= $sort_option === 'price_low' ? 'selected' : '' ?>>Price (Low to High)
                    </option>
                    <option value="price_high" <?= $sort_option === 'price_high' ? 'selected' : '' ?>>Price (High to Low)
                    </option>
                </select>
            </form>
        </div>
    </div>

    <div class="row">
        <?php
        // Base query to get all meals
        $query = "
            SELECT m.*, u.username, 
                   COALESCE(AVG(r.rating), 0) as avg_rating
            FROM meals m 
            JOIN users u ON m.user_id = u.id 
            LEFT JOIN reviews r ON r.chef_id = u.id
            GROUP BY m.id
        ";

        // Add sorting based on selected option
        switch ($sort_option) {
            case 'distance':
                if ($userLat !== null && $userLng !== null) {
                    $query .= " ORDER BY 
                        CASE 
                            WHEN pickup_location LIKE '%,%' THEN 
                                (6371 * acos(
                                    cos(radians(?)) * cos(radians(SUBSTRING_INDEX(pickup_location, ',', 1))) * 
                                    cos(radians(SUBSTRING_INDEX(pickup_location, ',', -1)) - radians(?)) + 
                                    sin(radians(?)) * sin(radians(SUBSTRING_INDEX(pickup_location, ',', 1)))
                                )) * 0.621371
                            ELSE 999999
                        END ASC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ddd", $userLat, $userLng, $userLat);
                } else {
                    $query .= " ORDER BY m.timestamp DESC";
                    $stmt = $conn->prepare($query);
                }
                break;
            case 'rating':
                $query .= " ORDER BY avg_rating DESC, m.timestamp DESC";
                $stmt = $conn->prepare($query);
                break;
            case 'price_low':
                $query .= " ORDER BY m.price ASC, m.timestamp DESC";
                $stmt = $conn->prepare($query);
                break;
            case 'price_high':
                $query .= " ORDER BY m.price DESC, m.timestamp DESC";
                $stmt = $conn->prepare($query);
                break;
            default: // recent
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
                            <p class="card-text">
                                <strong>Rating:</strong>
                                <?php
                                $rating = number_format($row['avg_rating'], 1);
                                echo $rating > 0 ? $rating . '/5' : 'No ratings yet';
                                ?>
                            </p>
                            <p class="card-text">
                                <strong>Price:</strong> $<?= number_format($row["price"], 2) ?>
                            </p>
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