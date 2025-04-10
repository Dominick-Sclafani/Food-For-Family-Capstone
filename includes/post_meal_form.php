<div class="container mt-4">
    <h2>Post a Meal</h2>
    <form id="meal-form" method="POST" action="post_meal.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Meal Title</label>
            <input type="text" class="form-control" name="title" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Estimated Time for Pickup</label>
            <input type="datetime-local" class="form-control" name="pickup_time" required>
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
            <input type="text" class="form-control" name="pickup_location" required>
        </div>


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