document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("signupForm");
  const modal = document.getElementById("confirmSignupModal");
  const modalDetails = document.getElementById("confirmSignupDetails");
  const confirmBtn = document.getElementById("confirmSignupBtn");
  const cancelBtn = document.getElementById("cancelSignupBtn");

  // ── Password Strength Meter ──
  const passwordInput = document.getElementById("password");
  const strengthBar = document.getElementById("password-strength-bar");
  const strengthText = document.getElementById("password-strength-text");

  if (passwordInput && strengthBar) {
    passwordInput.addEventListener("input", function () {
      const val = passwordInput.value;
      let score = 0;

      // Length check
      if (val.length >= 8) score += 1;
      if (val.length >= 12) score += 1;

      // Complexity checks
      if (/[A-Z]/.test(val)) score += 1;
      if (/[a-z]/.test(val)) score += 1;
      if (/\d/.test(val)) score += 1;
      if (/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/.test(val)) score += 1;

      // Map score to strength (0-6)
      const levels = [
        { label: 'Very Weak', color: '#dc3545', width: '10%' },
        { label: 'Weak',      color: '#dc3545', width: '25%' },
        { label: 'Fair',      color: '#ffc107', width: '45%' },
        { label: 'Good',      color: '#17a2b8', width: '65%' },
        { label: 'Strong',    color: '#28a745', width: '85%' },
        { label: 'Very Strong', color: '#28a745', width: '100%' },
      ];

      const idx = Math.min(score, levels.length - 1);
      strengthBar.style.width = levels[idx].width;
      strengthBar.style.backgroundColor = levels[idx].color;
      strengthBar.style.transition = 'all 0.3s ease';

      if (strengthText) {
        strengthText.textContent = levels[idx].label;
        strengthText.style.color = levels[idx].color;
      }
    });
  }

  form.addEventListener("submit", function (event) {
      event.preventDefault();

      // Fetch form values
      let email = document.getElementById("email").value.trim();
      let firstName = document.getElementById("first_name").value.trim();
      let lastName = document.getElementById("last_name").value.trim();
      let contactNumber = document.getElementById("phone").value.trim();
      let password = document.getElementById("password").value;
      let confirmPassword = document.getElementById("confirm_password").value;

      // Validate contact number (must be 11 digits)
      if (!/^\d{11}$/.test(contactNumber)) {
          alert("Contact number must be exactly 11 digits.");
          return;
      }

      // Validate password match
      if (password !== confirmPassword) {
          alert("Passwords do not match.");
          return;
      }

      // Client-side password complexity check
      if (password.length < 8) {
          alert("Password must be at least 8 characters long.");
          return;
      }
      if (!/[A-Z]/.test(password)) {
          alert("Password must contain at least one uppercase letter.");
          return;
      }
      if (!/\d/.test(password)) {
          alert("Password must contain at least one number.");
          return;
      }
      if (!/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/.test(password)) {
          alert("Password must contain at least one special character.");
          return;
      }

      // Show confirmation modal with details
      modalDetails.innerHTML = `
        <strong>Email:</strong> ${email}<br>
        <strong>First Name:</strong> ${firstName}<br>
        <strong>Last Name:</strong> ${lastName}<br>
        <strong>Contact Number:</strong> ${contactNumber}<br>
        <br>
        <em>Are you sure you want to proceed?</em>
      `;
      modal.style.display = 'flex';
  });

  function closeModal() {
      modal.style.display = 'none';
  }

  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function(e) {
      if (e.target === modal) closeModal();
  });

  confirmBtn.addEventListener('click', function() {
      closeModal();
      form.submit();
  });
});