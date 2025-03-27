<?php
// Get reviews for this chef
$stmt = $conn->prepare("
    SELECT r.*, u.username, m.title as meal_title
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN meals m ON r.meal_id = m.id
    WHERE r.chef_id = ? 
    ORDER BY r.timestamp DESC
");
$stmt->bind_param("i", $chef_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

// Calculate average rating
$avg_stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE chef_id = ?");
$avg_stmt->bind_param("i", $chef_id);
$avg_stmt->execute();
$avg_result = $avg_stmt->get_result();
$avg_data = $avg_result->fetch_assoc();

if ($reviews_result->num_rows > 0): ?>
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Chef Reviews</h5>

            <!-- Average Rating -->
            <div class="text-center mb-4">
                <h6 class="fw-bold">Overall Rating</h6>
                <div class="d-flex align-items-center justify-content-center">
                    <div class="rating-display">
                        <?php
                        $avg_rating = round($avg_data['avg_rating'], 1);
                        echo "<span class='fw-bold'>$avg_rating</span>/5";
                        ?>
                    </div>
                    <span class="ms-2 fw-semibold">(<?= $avg_data['total_reviews'] ?> reviews)</span>
                </div>
            </div>

            <!-- Individual Reviews -->
            <div class="reviews-list">
                <?php while ($review = $reviews_result->fetch_assoc()): ?>
                    <div class="review-item border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="w-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($review['username']) ?></h6>
                                    <small class="text-muted"><?= date('M d, Y', strtotime($review['timestamp'])) ?></small>
                                        </div>
                                        <div class="text-muted mb-1">
                                            <small>Review for: <?= htmlspecialchars($review['meal_title']) ?></small>
                                </div>
                                <div class="rating-display mb-2">
                                    <span class="fw-bold"><?= $review['rating'] ?>/5</span>
                                </div>
                                <p class="mb-0 text-muted"><?= htmlspecialchars($review['comment']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
<?php endif;

$stmt->close();
$avg_stmt->close();
?>