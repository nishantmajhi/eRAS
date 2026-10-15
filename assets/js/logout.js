document.addEventListener("DOMContentLoaded", () => {
  document.querySelector("header button").addEventListener("click", () => {
    window.location.href = window.location.origin + "/logout.php";
  });
});
