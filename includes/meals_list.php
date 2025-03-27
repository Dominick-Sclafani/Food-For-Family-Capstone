<div class="container mt-5">
    <h2 class="text-center">Available Meals</h2>
    <div class="row">
        <?php
        // Updated query to include user_id for chef profile links
        $result = $conn->query("
            SELECT m.id, m.title, m.image, m.timestamp, m.user_id, u.username 
            FROM meals m 
            JOIN users u ON m.user_id = u.id 
            ORDER BY m.timestamp DESC
        ");

        if ($result && $result->num_rows > 0):
            while ($row = $result->fetch_assoc()): ?>
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