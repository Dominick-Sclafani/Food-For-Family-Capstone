function toggleForm(type) {
  if (type === "login") {
    document.getElementById("login-form-container").style.display = "block";
    document.getElementById("register-form-container").style.display = "none";
  } else if (type === "register") {
    document.getElementById("register-form-container").style.display = "block";
    document.getElementById("login-form-container").style.display = "none";
  }
}
