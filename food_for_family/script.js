function showForm(type) {
  let formHTML = ""; // Empty form string

  if (type === "login") {
    // If login is selected
    formHTML = `
            <form id="login-form" method="POST" action="auth.php">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Login</button>
            </form>`;
  } else {
    // If register form is selected
    formHTML = `
            <form id="register-form" method="POST" action="auth.php">
                <input type="hidden" name="action" value="register">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>`;
  }

  $("#form-container").html(formHTML); // Insert form into HTML container

  //meal posting ajax script
  $(document).on("submit", "#meal-form", function (e) {
    e.preventDefault(); // Prevent empty form submission
    let formData = $(this).serialize();

    $.post("post_meal.php", formData, function (response) {
      response = response.trim(); // Remove whitespace

      if (response === "success") {
        alert("Meal posted successfully!");
        location.reload(); // Refresh to show the new meal
      } else {
        alert("Error: " + response);
      }
    });
  });
}
