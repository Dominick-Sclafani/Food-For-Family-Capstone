<?php
if (isset($_SESSION["user_id"])) {
    // Check if user has any unreviewed purchased meals from this chef
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id as purchase_id, m.id as meal_id, m.title as meal_title
        FROM purchases p
        JOIN meals m ON p.meal_id = m.id
        WHERE p.user_id = ? 
        AND m.user_id = ?
        AND NOT EXISTS (
            SELECT 1 FROM reviews r 
            WHERE r.user_id = p.user_id 
            AND r.meal_id = m.id
        )
    ");
    $stmt->bind_param("ii", $_SESSION["user_id"], $chef_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0): ?>
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h5 class="card-title text-center mb-4">Write a Review</h5>
                <form action="includes/review.php" method="POST">
                    <input type="hidden" name="chef_id" value="<?= $chef_id ?>">

                    <div class="mb-3">
                        <label for="meal_id" class="form-label">Select Meal to Review</label>
                        <select class="form-select" id="meal_id" name="meal_id" required>
                            <option value="">Choose a meal...</option>
                            <?php while ($meal = $result->fetch_assoc()): ?>
                                <option value="<?= $meal['meal_id'] ?>"><?= htmlspecialchars($meal['meal_title']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="rating" class="form-label">Rating</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="">Select rating...</option>
                            <option value="5">5 - Excellent</option>
                            <option value="4">4 - Very Good</option>
                            <option value="3">3 - Good</option>
                            <option value="2">2 - Fair</option>
                            <option value="1">1 - Poor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3"
                            placeholder="Share your experience with this meal..." required></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif;
    $stmt->close();
}
?>