function showForm(type) {
  let formHTML = ""; //empty form string

  if (type === "login") {
    //if login is selected
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
    //if register form is selected
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

  $("#form-container").html(formHTML); //insert form into html contianer

  $("#login-form, #register-form").on("submit", function (e) {
    e.preventDefault();
    let formData = $(this).serialize() + `&action=${type}`; //sets form data to url format

    $.post("auth.php", formData, function (response) {
      //sends asynch request to php
      if (response.trim() === "success") {
        location.reload();
      } else {
        alert("Error: " + response);
      }
    });
  });
}
