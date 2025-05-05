<?php
include('includes/session_start.php');
include('includes/header.php');

// Get top chefs for the week
$top_chefs_query = "
    SELECT 
        u.id,
        u.username,
        COUNT(DISTINCT m.id) as total_meals,
        COUNT(DISTINCT p.id) as total_purchases,
        COALESCE(AVG(r.rating), 0) as avg_rating
    FROM users u
    LEFT JOIN meals m ON u.id = m.user_id
    LEFT JOIN purchases p ON m.id = p.meal_id
    LEFT JOIN reviews r ON u.id = r.chef_id
    WHERE u.role = 'chef' 
    AND u.verification_status = 'approved'
    AND m.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.id, u.username
    ORDER BY total_purchases DESC, avg_rating DESC
    LIMIT 5";

$top_chefs = $conn->query($top_chefs_query);

// Get activity data for heat map
$activity_query = "
    SELECT 
        SUBSTRING_INDEX(pickup_location, ',', 1) as lat,
        SUBSTRING_INDEX(pickup_location, ',', -1) as lng,
        COUNT(*) as activity_count
    FROM meals
    WHERE pickup_location LIKE '%,%'
    GROUP BY lat, lng";

$activity_data = $conn->query($activity_query);
$heatmap_data = [];
while ($row = $activity_data->fetch_assoc()) {
    $heatmap_data[] = [
        floatval($row['lat']),
        floatval($row['lng']),
        intval($row['activity_count'])
    ];
}
?>

<div class="container mt-5">
    <!-- Mission Statement Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-lg">
                <div class="card-body">
                    <h2 class="text-center mb-4">Our Mission</h2>
                    <p class="lead text-center">
                        Food For Family exists to connect communities through home-cooked meals.
                        Our mission is to reduce food waste, promote culinary talent, and ensure
                        families can access fresh, affordable, and delicious meals made by local cooks.
                    </p>
                    <div class="row mt-4">
                        <div class="col-md-4 text-center">
                            <!--fa = font awesome-->
                            <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                            <h4>Home-Cooked Meals</h4>
                            <p>Supporting local chefs who wish to share their passion for cooking</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-users fa-3x mb-3 text-success"></i>
                            <h4>Community Building</h4>
                            <p>Creating connections through food</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-star fa-3x mb-3 text-warning"></i>
                            <h4>Quality Assurance</h4>
                            <p>Ensuring the highest standards in food safety and quality</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Section -->
    <div class="row">
        <!-- Top Chefs Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg h-100">
                <div class="card-body">
                    <h3 class="card-title mb-4">Top Chefs This Week</h3>
                    <?php if ($top_chefs->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($chef = $top_chefs->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-1">
                                                <a href="profile.php?id=<?= $chef['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($chef['username']) ?>
                                                </a>
                                            </h5>
                                            <small class="text-muted">
                                                <?= $chef['total_meals'] ?> meals posted
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="text-warning">
                                                <?= number_format($chef['avg_rating'], 1) ?>/5
                                                <i class="fas fa-star"></i>
                                            </div>
                                            <small class="text-success">
                                                <?= $chef['total_purchases'] ?> purchases
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No chef activity this week</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activity Heat Map Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-lg h-100">
                <div class="card-body">
                    <h3 class="card-title mb-4">Activity Heat Map</h3>
                    <div id="heatmap" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Leaflet Heatmap Plugin -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
    // Initialize the map
    const map = L.map('heatmap').setView([40.7128, -74.0060], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add heatmap layer
    const heatData = <?= json_encode($heatmap_data) ?>;
    const heat = L.heatLayer(heatData, {
        radius: 25,
        blur: 15,
        maxZoom: 10,
        max: 1.0,
        gradient: {
            0.4: 'blue',
            0.6: 'lime',
            0.8: 'yellow',
            1.0: 'red'
        }
    }).addTo(map);

    // Fit map bounds to show all heatmap points
    if (heatData.length > 0) {
        const bounds = heatData.map(point => [point[0], point[1]]);
        map.fitBounds(bounds);
    }
</script>

<?php include('includes/footer.php'); ?>