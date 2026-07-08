document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("signupForm");
  const modal = document.getElementById("confirmSignupModal");
  const modalDetails = document.getElementById("confirmSignupDetails");
  const confirmBtn = document.getElementById("confirmSignupBtn");
  const cancelBtn = document.getElementById("cancelSignupBtn");

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