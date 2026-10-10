document.addEventListener("DOMContentLoaded", function () {
  const passwordInput = document.getElementById("password");
  const toggle = document.getElementById("toggle-password");
  const eyeShow = document.getElementById("eye-show");
  const eyeHide = document.getElementById("eye-hide");

  if (!passwordInput || !toggle) return;

  toggle.addEventListener("click", function () {
    const willShow = passwordInput.type === "password";

    passwordInput.type = willShow ? "text" : "password";
    toggle.setAttribute("aria-pressed", willShow ? "true" : "false");
    toggle.setAttribute("aria-label", willShow ? "Hide password" : "Show password");

    if (eyeShow) eyeShow.hidden = willShow;
    if (eyeHide) eyeHide.hidden = !willShow;
  });
});
