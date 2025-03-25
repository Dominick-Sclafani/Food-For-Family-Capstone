<div class="container mt-4 text-center">
    <h2>Want to post your meals and be a "Home Cook" with us?</h2>
    <p>Complete the form below to apply. <small>You must be at least 23 years old to apply.</small></p>

    <form method="POST" action="chef_reg.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" class="form-control" name="dob" id="dob" required>
            <small class="text-danger" id="dob-warning" style="display:none;">
                You must be at least 23 years old.
            </small>
        </div>

        <div class="mb-3">
            <label class="form-label">Why do you want to become a chef?</label>
            <textarea class="form-control" name="reason" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Upload an ID with Date Of birth</label>
            <input type="file" class="form-control" name="id_document" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>

        <button type="submit" class="btn btn-warning">Submit Application</button>
    </form>
</div>
