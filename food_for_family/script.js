function showForm(type) {
  let formHTML = "";

  if (type === "login") {
    formHTML = `
            <form id="login-form">
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
    formHTML = `
            <form id="register-form">
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

  $("#form-container").html(formHTML);

  $("#login-form, #register-form").on("submit", function (e) {
    e.preventDefault();
    let formData = $(this).serialize() + `&action=${type}`;

    $.post("auth.php", formData, function (response) {
      if (response.trim() === "success") {
        location.reload();
      } else {
        alert("Error: " + response);
      }
    });
  });
}
