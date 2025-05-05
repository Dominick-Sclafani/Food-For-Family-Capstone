<div class="container mt-4">
    <h2>Post a Meal</h2>
    <form id="meal-form" method="POST" action="post_meal.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Meal Title</label>
            <input type="text" class="form-control" name="title" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Estimated Time for Pickup</label>
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Start Time</label>
                    <input type="datetime-local" class="form-control" name="pickup_time" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">End Time</label>
                    <input type="datetime-local" class="form-control" name="pickup_end_time" required>
                </div>
            </div>
            <small class="text-muted">Please set a reasonable time window for customers to pick up their meals</small>
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Ingredients</label>
            <textarea class="form-control" name="ingredients"></textarea>
        </div>

        <!-- Allergen Dropdown -->
        <div class="mb-3">
            <label class="form-label">Common Allergens</label>
            <div class="d-flex flex-wrap">
                <?php
                $allergens = [
                    "Peanuts",
                    "Tree Nuts",
                    "Dairy",
                    "Eggs",
                    "Shellfish",
                    "Fish",
                    "Soy",
                    "Wheat",
                    "Sesame",
                    "Gluten"
                ];
                foreach ($allergens as $allergen): ?>
                    <div class="form-check m-2">
                        <input class="form-check-input" type="checkbox" name="allergens[]" value="<?= $allergen ?>"
                            id="<?= strtolower(str_replace(' ', '-', $allergen)); ?>">
                        <label class="form-check-label" for="<?= strtolower(str_replace(' ', '-', $allergen)); ?>">
                            <?= $allergen ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Pickup Location</label>
            <div class="input-group">
                <input type="text" class="form-control" id="pickup_location" name="pickup_location" required>
                <button class="btn btn-outline-secondary" type="button" id="searchLocation">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
            <small class="text-muted">Enter an address and click search to verify location</small>
            <div id="selected-address" class="mt-2 text-success" style="display: none;">
                <i class="bi bi-check-circle"></i> Address verified
            </div>
        </div>

        <!-- Hidden fields for coordinates -->
        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">

        <div class="mb-3">
            <label class="form-label">Price ($)</label>
            <input type="number" class="form-control" name="price" step="0.01" min="0" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload Meal Image</label>
            <input type="file" class="form-control" name="meal_image" accept="image/*">
        </div>

        <button type="submit" class="btn btn-success">Post Meal</button>
    </form>
</div>

<script>
    // Function to update coordinates and display
    function updateLocation(lat, lng, address) {
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        document.getElementById('pickup_location').value = address;
        const selectedAddress = document.getElementById('selected-address');
        selectedAddress.style.display = 'block';
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
                    // Single result - update immediately
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    updateLocation(lat, lng, data[0].display_name);
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

        // Update location
        updateLocation(lat, lng, result.display_name);

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

    // Form submission validation
    document.getElementById('meal-form').addEventListener('submit', function (e) {
        const lat = document.getElementById('latitude').value;
        const lng = document.getElementById('longitude').value;
        if (!lat || !lng) {
            e.preventDefault();
            alert('Please search for and select a valid pickup location');
        }
    });
</script>