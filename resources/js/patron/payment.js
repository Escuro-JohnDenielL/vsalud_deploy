document.addEventListener("DOMContentLoaded", function () {
    // Form elements
    const orderForm = document.getElementById("orderForm");
    const paymentInstructions = document.getElementById("paymentInstructions");
    const generateOrderBtn = document.getElementById("generateOrder");
    const backButton = document.getElementById("backButton");
    const submitReceiptBtn = document.getElementById("submitReceipt");
    const receiptForm = document.getElementById("receiptForm");
    const successMessage = document.getElementById("successMessage");
    const loader = document.querySelector(".loader");

    // Input elements
    const fullNameInput = document.getElementById("fullName");
    const emailInput = document.getElementById("email");
    const downpaymentTypeSelect = document.getElementById("downpaymentType");
    const paymentMethodSelect = document.getElementById("paymentMethod");
    const reservationCode = document.getElementById("reservation_code");
    const receiptUpload = document.getElementById("receiptUpload");

    // Error elements
    const fullNameError = document.getElementById("fullNameError");
    const emailError = document.getElementById("emailError");
    const receiptError = document.getElementById("receiptError");

    // Display elements
    const paymentBadge = document.getElementById("paymentBadge");
    const customerNameSpan = document.getElementById("customerName");
    const trackingNumberSpan = document.getElementById("trackingNumber");
    const customerNameSpans = document.querySelectorAll(".customer-name");

    // Payment method sections
    const gcashInstructions = document.getElementById("gcashInstructions");
    const bankInstructions = document.getElementById("bankInstructions");
    const cashInstructions = document.getElementById("cashInstructions");

    // Utility functions
    function generateReferenceNumber() {
        const timestamp = new Date().getTime().toString().slice(-6);
        const random = Math.floor(Math.random() * 10000)
            .toString()
            .padStart(4, "0");
        return "VS-" + timestamp + "-" + random;
    }

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.toLowerCase());
    }

    function validateForm() {
        let isValid = true;

        // Reset previous error states
        fullNameInput.classList.remove("error");
        emailInput.classList.remove("error");
        fullNameError.style.display = "none";
        emailError.style.display = "none";

        // Validate full name
        const fullName = fullNameInput.value.trim();
        if (fullName === "" || fullName.length < 2) {
            fullNameError.textContent =
                "Please enter a valid full name (at least 2 characters).";
            fullNameError.style.display = "block";
            fullNameInput.classList.add("error");
            isValid = false;
        }

        // Validate email
        const email = emailInput.value.trim();
        if (email === "" || !isValidEmail(email)) {
            emailError.textContent = "Please enter a valid email address.";
            emailError.style.display = "block";
            emailInput.classList.add("error");
            isValid = false;
        }

        return isValid;
    }

    function validateReceipt() {
        receiptError.style.display = "none";

        if (receiptUpload.files.length === 0) {
            receiptError.textContent = "Please upload your payment receipt.";
            receiptError.style.display = "block";
            return false;
        }

        const file = receiptUpload.files[0];
        const allowedTypes = [
            "image/jpeg",
            "image/jpg",
            "image/png",
            "application/pdf",
        ];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) {
            receiptError.textContent =
                "Please upload a valid file (JPG, PNG, or PDF).";
            receiptError.style.display = "block";
            return false;
        }

        if (file.size > maxSize) {
            receiptError.textContent = "File size must be less than 5MB.";
            receiptError.style.display = "block";
            return false;
        }

        return true;
    }

    function updatePaymentInstructions() {
        const fullName = fullNameInput.value.trim();
        const paymentType = downpaymentTypeSelect.value;
        const paymentMethod = paymentMethodSelect.value;

        // Generate and display reference number
        // const trackingCode = generateReferenceNumber();
        // trackingNumberSpan.textContent = trackingCode;

        // Update customer name in all locations
        customerNameSpan.textContent = fullName;
        customerNameSpans.forEach((span) => {
            span.textContent = fullName;
        });

        // Update payment badge
        let badgeText = "FULL PAYMENT";
        if (paymentType === "half") {
            badgeText = "25% DOWNPAYMENT";
        }
        paymentBadge.textContent = badgeText;

        // Hide all payment method instructions
        gcashInstructions.style.display = "none";
        bankInstructions.style.display = "none";
        cashInstructions.style.display = "none";

        // Show relevant payment method instructions
        switch (paymentMethod) {
            case "gcash":
                gcashInstructions.style.display = "block";
                break;
            case "bank":
                bankInstructions.style.display = "block";
                break;
            case "cash":
                cashInstructions.style.display = "block";
                break;
        }
    }

    function smoothScrollTo(element) {
        const offsetTop = element.offsetTop - 20;
        window.scrollTo({
            top: offsetTop,
            behavior: "smooth",
        });
    }

    // Event listeners
    generateOrderBtn.addEventListener("click", function (e) {
        e.preventDefault();

        if (validateForm()) {
            updatePaymentInstructions();

            // Hide order form and show payment instructions
            orderForm.style.display = "none";
            paymentInstructions.style.display = "block";
            successMessage.style.display = "none";

            // Scroll to payment instructions
            setTimeout(() => {
                smoothScrollTo(paymentInstructions);
            }, 100);
        }
    });

    backButton.addEventListener("click", function (e) {
        e.preventDefault();

        // Show order form and hide payment instructions
        orderForm.style.display = "block";
        paymentInstructions.style.display = "none";
        successMessage.style.display = "none";

        // Reset receipt upload
        receiptUpload.value = "";
        receiptError.style.display = "none";

        // Scroll to top of form
        smoothScrollTo(orderForm);
    });

    // receiptForm.addEventListener('submit', function(e) {
    //     e.preventDefault();

    //     if (validateReceipt()) {
    //         // Disable submit button and show loader
    //         submitReceiptBtn.disabled = true;
    //         submitReceiptBtn.textContent = 'Submitting...';
    //         loader.style.display = 'block';
    //         receiptError.style.display = 'none';

    //         // Simulate form submission
    //         setTimeout(() => {
    //             // Hide loader and re-enable button
    //             loader.style.display = 'none';
    //             submitReceiptBtn.disabled = false;
    //             submitReceiptBtn.textContent = 'Submit Receipt';

    //             // Hide receipt form and show success message
    //             receiptForm.style.display = 'none';
    //             successMessage.style.display = 'block';

    //             // Scroll to success message
    //             setTimeout(() => {
    //                 smoothScrollTo(successMessage);
    //             }, 100);
    //         }, 2000);
    //     }
    // });

    // Real-time validation

    receiptForm.addEventListener("submit", function (e) {
        e.preventDefault();

        if (validateReceipt()) {
            const formData = new FormData();
            formData.append("full_name", fullNameInput.value.trim());
            formData.append("email", emailInput.value.trim());
            formData.append("payment_type", downpaymentTypeSelect.value);
            formData.append("payment_method", paymentMethodSelect.value);
            formData.append("reservation_code", reservationCode.value);
            formData.append("receipt", receiptUpload.files[0]);

            // Disable UI
            submitReceiptBtn.disabled = true;
            submitReceiptBtn.textContent = "Submitting...";
            loader.style.display = "block";

            fetch("/submit-payment", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: formData,
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        receiptForm.style.display = "none";
                        successMessage.style.display = "block";
                        trackingNumberSpan.textContent = data.tracking_code;
                        customerNameSpans.forEach(
                            (span) =>
                                (span.textContent = fullNameInput.value.trim())
                        );
                    } else {
                        alert("Failed to submit payment.");
                    }
                })
                .catch(() => alert("An error occurred."))
                .finally(() => {
                    loader.style.display = "none";
                    submitReceiptBtn.disabled = false;
                    submitReceiptBtn.textContent = "Submit Receipt";
                });
        }
    });

    fullNameInput.addEventListener("input", function () {
        if (this.classList.contains("error") && this.value.trim().length >= 2) {
            this.classList.remove("error");
            fullNameError.style.display = "none";
        }
    });

    emailInput.addEventListener("input", function () {
        if (
            this.classList.contains("error") &&
            isValidEmail(this.value.trim())
        ) {
            this.classList.remove("error");
            emailError.style.display = "none";
        }
    });

    receiptUpload.addEventListener("change", function () {
        if (this.files.length > 0) {
            receiptError.style.display = "none";
        }
    });

    // Prevent form submission on Enter key in input fields
    [fullNameInput, emailInput].forEach((input) => {
        input.addEventListener("keypress", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                generateOrderBtn.click();
            }
        });
    });
});
