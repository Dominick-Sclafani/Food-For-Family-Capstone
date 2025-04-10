<?php
session_start();
require "db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    $_SESSION["error"] = "Please log in to edit meals.";
    header("Location: login.php");
    exit;
}

// Check if meal ID is provided
if (!isset($_GET["id"])) {
    $_SESSION["error"] = "No meal selected.";
    header("Location: index.php");
    exit;
}

$meal_id = $_GET["id"];

// Get meal details
$stmt = $conn->prepare("SELECT * FROM meals WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $meal_id, $_SESSION["user_id"]);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION["error"] = "Meal not found or you don't have permission to edit it.";
    header("Location: index.php");
    exit;
}

$meal = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $description = trim($_POST["description"]);
    $ingredients = trim($_POST["ingredients"]);
    $allergies = trim($_POST["allergies"]);
    $pickup_location = trim($_POST["pickup_location"]);
    $price = floatval($_POST["price"]);

    // Validate input
    if (empty($title) || empty($description) || empty($ingredients) || empty($pickup_location) || $price <= 0) {
        $_SESSION["error"] = "All fields are required and price must be greater than 0.";
    } elseif (!strpos($pickup_location, ',')) {
        $_SESSION["error"] = "Please enter a valid address and wait for the map to update.";
    } else {
        // Update meal in database
        $stmt = $conn->prepare("UPDATE meals SET title = ?, description = ?, ingredients = ?, allergies = ?, pickup_location = ?, price = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssssdii", $title, $description, $ingredients, $allergies, $pickup_location, $price, $meal_id, $_SESSION["user_id"]);

        if ($stmt->execute()) {
            $_SESSION["success"] = "Meal updated successfully!";
            header("Location: meal_details.php?id=" . $meal_id);
            exit;
        } else {
            $_SESSION["error"] = "Error updating meal. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Meal - Food For Family</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <!-- Add Leaflet CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
</head>

<body>
    <?php include('includes/navbar.php'); ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Edit Meal</h2>

                        <?php if (isset($_SESSION["error"])): ?>
                            <div class="alert alert-danger">
                                <?= $_SESSION["error"]; ?>
                                <?php unset($_SESSION["error"]); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="editMealForm">
                            <div class="mb-3">
                                <label for="title" class="form-label">Meal Title</label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="<?= htmlspecialchars($meal["title"]); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    required><?= htmlspecialchars($meal["description"]); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="ingredients" class="form-label">Ingredients</label>
                                <textarea class="form-control" id="ingredients" name="ingredients" rows="3"
                                    required><?= htmlspecialchars($meal["ingredients"]); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="allergies" class="form-label">Allergies</label>
                                <textarea class="form-control" id="allergies" name="allergies"
                                    rows="2"><?= htmlspecialchars($meal["allergies"]); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="pickup_location" class="form-label">Pickup Location</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="pickup_location" name="pickup_location"
                                        value="<?= htmlspecialchars($meal["pickup_location"]); ?>" required>
                                    <button class="btn btn-outline-secondary" type="button" id="searchLocation">
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <small class="text-muted">Enter an address or use your current location</small>
                            </div>

                            <!-- Add map container -->
                            <div class="mb-3">
                                <div id="map" style="height: 300px;"></div>
                                <div id="distance-info" class="mt-2 text-muted"></div>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price ($)</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                                    value="<?= number_format($meal["price"], 2); ?>" required>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Meal</button>
                                <a href="meal_details.php?id=<?= $meal_id ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Get initial coordinates from pickup_location
        const pickupLocation = "<?= htmlspecialchars($meal["pickup_location"]); ?>";
        const coords = pickupLocation.split(",");
        const initialLat = parseFloat(coords[0]) || 40.7128;
        const initialLng = parseFloat(coords[1]) || -74.0060;

        // Initialize map
        const map = L.map('map').setView([initialLat, initialLng], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Custom marker icon
        const pickupIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        // Add marker for current location
        const marker = L.marker([initialLat, initialLng], { icon: pickupIcon }).addTo(map);
        marker.bindPopup("Current Pickup Location").openPopup();

        // Function to update marker position
        function updateMarker(lat, lng) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 14);
            marker.bindPopup("New Pickup Location").openPopup();
            document.getElementById('pickup_location').value = `${lat},${lng}`;
            updateDistanceInfo(lat, lng);
        }

        // Function to update distance information
        function updateDistanceInfo(lat, lng) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const distance = calculateDistance(userLat, userLng, lat, lng);
                    document.getElementById('distance-info').innerHTML =
                        `Distance from your location: ${distance.toFixed(1)} miles`;
                }, () => {
                    document.getElementById('distance-info').innerHTML =
                        'Unable to calculate distance. Please enable location access.';
                });
            }
        }

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

        // Function to geocode address using Nominatim
        async function geocodeAddress(address) {
            try {
                // Show loading state
                const searchBtn = document.getElementById('searchLocation');
                const originalText = searchBtn.innerHTML;
                searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Searching...';
                searchBtn.disabled = true;

                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=5`);
                const data = await response.json();

                // Reset button state
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;

                if (data && data.length > 0) {
                    if (data.length === 1) {
                        // Single result - update map immediately
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        updateMarker(lat, lng);
                        return true;
                    } else {
                        // Multiple results - show selection dialog
                        showAddressResults(data);
                        return false;
                    }
                } else {
                    alert('No results found. Please try a different address.');
                    return false;
                }
            } catch (error) {
                console.error('Error geocoding address:', error);
                alert('Error searching for address. Please try again.');
                return false;
            }
        }

        // Function to show address selection dialog
        function showAddressResults(results) {
            const resultsContainer = document.createElement('div');
            resultsContainer.className = 'address-results';
            resultsContainer.innerHTML = `
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Select Address</h5>
                        <button type="button" class="btn-close" onclick="closeAddressDialog()"></button>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            ${results.map((result, index) => `
                                <button type="button" class="list-group-item list-group-item-action" 
                                    onclick="selectAddress(${index})">
                                    ${result.display_name}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;

            // Add styles
            const style = document.createElement('style');
            style.textContent = `
                .address-results {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    z-index: 1050;
                    width: 90%;
                    max-width: 500px;
                }
                .address-results .card {
                    box-shadow: 0 0 20px rgba(0,0,0,0.2);
                }
                .address-results .list-group-item {
                    cursor: pointer;
                }
                .address-results .list-group-item:hover {
                    background-color: #f8f9fa;
                }
                .modal-backdrop {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                    z-index: 1040;
                }
            `;
            document.head.appendChild(style);

            // Add overlay
            const overlay = document.createElement('div');
            overlay.className = 'modal-backdrop';
            overlay.onclick = closeAddressDialog;
            document.body.appendChild(overlay);
            document.body.appendChild(resultsContainer);

            // Store results for selection
            window.addressResults = results;
        }

        // Function to close the address dialog
        function closeAddressDialog() {
            const dialog = document.querySelector('.address-results');
            const overlay = document.querySelector('.modal-backdrop');
            if (dialog) dialog.remove();
            if (overlay) overlay.remove();
        }

        // Function to handle address selection
        function selectAddress(index) {
            const result = window.addressResults[index];
            const lat = parseFloat(result.lat);
            const lng = parseFloat(result.lon);

            // Update map and input
            updateMarker(lat, lng);
            document.getElementById('pickup_location').value = result.display_name;

            // Close the dialog
            closeAddressDialog();
        }

        // Handle search button click
        document.getElementById('searchLocation').addEventListener('click', async function () {
            const address = document.getElementById('pickup_location').value.trim();
            if (address.length > 5 && !address.includes(',')) {
                await geocodeAddress(address);
            } else if (address.length <= 5) {
                alert('Please enter a more specific address');
            }
        });

        // Handle address input changes
        let geocodeTimeout;
        document.getElementById('pickup_location').addEventListener('input', function () {
            clearTimeout(geocodeTimeout);
            geocodeTimeout = setTimeout(async () => {
                const address = this.value;
                if (address.length > 5 && !address.includes(',')) {
                    await geocodeAddress(address);
                }
            }, 1000);
        });

        // Form submission validation
        document.getElementById('editMealForm').addEventListener('submit', function (e) {
            const pickupLocation = document.getElementById('pickup_location').value;
            if (!pickupLocation.includes(',')) {
                e.preventDefault();
                alert('Please enter a valid address and wait for the map to update.');
            }
        });

        // Initialize distance info
        updateDistanceInfo(initialLat, initialLng);
    </script>
</body>

</html>
<?php $conn->close(); ?>