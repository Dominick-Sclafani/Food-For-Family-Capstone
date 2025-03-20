function toggleForm(type) {
  if (type === "login") {
    document.getElementById("login-form-container").style.display = "block";
    document.getElementById("register-form-container").style.display = "none";
  } else if (type === "register") {
    document.getElementById("register-form-container").style.display = "block";
    document.getElementById("login-form-container").style.display = "none";
  }
}
document.addEventListener("DOMContentLoaded", function () {
  const passwordInput = document.getElementById("password");
  const passwordError = document.getElementById("password-error");
  const lengthRequirement = document.getElementById("length");
  const uppercaseRequirement = document.getElementById("uppercase");
  const lowercaseRequirement = document.getElementById("lowercase");
  const numberRequirement = document.getElementById("number");
  const registerForm = document.getElementById("register-form");

  function validatePassword(password) {
    const isLongEnough = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /\d/.test(password);

    lengthRequirement.classList.toggle("text-success", isLongEnough);
    lengthRequirement.classList.toggle("text-danger", !isLongEnough);

    uppercaseRequirement.classList.toggle("text-success", hasUppercase);
    uppercaseRequirement.classList.toggle("text-danger", !hasUppercase);

    lowercaseRequirement.classList.toggle("text-success", hasLowercase);
    lowercaseRequirement.classList.toggle("text-danger", !hasLowercase);

    numberRequirement.classList.toggle("text-success", hasNumber);
    numberRequirement.classList.toggle("text-danger", !hasNumber);

    return isLongEnough && hasUppercase && hasLowercase && hasNumber;
  }

  // Update password requirements dynamically
  passwordInput.addEventListener("input", function () {
    validatePassword(passwordInput.value);
  });

  // Prevent form submission if password does not meet requirements
  registerForm.addEventListener("submit", function (e) {
    if (!validatePassword(passwordInput.value)) {
      e.preventDefault(); // Stop form submission
      passwordError.style.display = "block"; // Show error message
    } else {
      passwordError.style.display = "none"; // Hide error if valid
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
  let dobInput = document.getElementById("dob");

  if (dobInput) {
    dobInput.addEventListener("change", function () {
      let dobValue = dobInput.value;
      let dobWarning = document.getElementById("dob-warning");

      if (dobValue) {
        let dob = new Date(dobValue);
        let today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        let monthDiff = today.getMonth() - dob.getMonth();
        let dayDiff = today.getDate() - dob.getDate();

        // Adjust age if birthday hasn't occurred yet this year
        if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
          age--;
        }

        if (age < 23) {
          dobWarning.style.display = "block";
          dobInput.setCustomValidity("You must be at least 23 years old.");
        } else {
          dobWarning.style.display = "none";
          dobInput.setCustomValidity("");
        }
      }
    });
  }
});
