document.addEventListener("DOMContentLoaded", function () {
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");
  const form = passwordInput.parentElement;

  usernameInput.addEventListener("input", function () {
    usernameInput.value = usernameInput.value.toUpperCase();
    
    if (usernameInput.value.includes("SDNT")) {
      if (passwordInput) {
        passwordInput.remove();
      }
    } else {
      if (!document.getElementById("password")) {
        const newPasswordInput = document.createElement("input");
        newPasswordInput.type = "password";
        newPasswordInput.id = "password";
        newPasswordInput.name = "password";
        newPasswordInput.placeholder = "password";
        newPasswordInput.required = true;
        form.insertBefore(newPasswordInput, form.querySelector("button"));
      }
    }
  });
});