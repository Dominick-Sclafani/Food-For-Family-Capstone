<!-- Home Cook Application Toggle Button -->
<div class="text-center mt-4">
  <button class="btn btn-warning" id="showApplicationBtn">Click here to apply as a Home Cook</button>
</div>

<!-- Hidden Home Cook Application Form -->
<div class="container mt-4" id="homeCookForm" style="display: none;">
  <form method="POST" action="chef_reg.php" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="full_name" class="form-label">Full Name</label>
      <input type="text" class="form-control" name="full_name" required>
    </div>

    <div class="mb-3">
      <label for="dob" class="form-label">Date of Birth</label>
      <input type="date" class="form-control" name="dob" required>
    </div>

    <div class="mb-3">
      <label for="reason" class="form-label">Why do you want to become a chef?</label>
      <textarea class="form-control" name="reason" rows="3" required></textarea>
    </div>

    <div class="mb-3">
      <label for="id_document" class="form-label">Upload an ID with Date Of Birth</label>
      <input type="file" class="form-control" name="id_document" accept="image/*,.pdf" required>
    </div>

    <button type="submit" class="btn btn-primary">Submit Application</button>
  </form>
</div>

<!-- Toggle Script -->
<script>
  document.getElementById("showApplicationBtn").addEventListener("click", function () {
    const form = document.getElementById("homeCookForm");
    form.style.display = form.style.display === "none" ? "block" : "none";
  });
</script>