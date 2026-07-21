console.log("admin/profile.js loaded ✅");

function generateId(length = 12) {
    const chars =
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    let id = "";
    for (let i = 0; i < length; i++) {
        id += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return id;
}
// Admin Profile JavaScript with Enhanced History System
document.addEventListener("DOMContentLoaded", function () {
    // Get modal elements
    const editModal = document.getElementById("edit-modal");
    const passwordModal = document.getElementById("password-modal");
    const successModal = document.getElementById("success-modal");
    const confirmModal = document.getElementById("confirm-modal");
    // Note: imageSelectionModal will be created dynamically

    // Get button elements
    const editBtn = document.getElementById("edit-profile-btn");
    const changePasswordLink = document.getElementById("change-password-link");
    // PROFILE PICTURE: commented out for now — re-enable later
    // const changePicBtn = document.getElementById("change-pic-btn");
    // const profilePicInput = document.getElementById("profile-pic-input");
    const successOkBtn = document.getElementById("success-ok-btn");
    const clearHistoryBtn = document.getElementById("clear-history-btn");
    const loadMoreBtn = document.getElementById("load-more-btn");

    // Get close buttons (these may not exist in HTML, so we'll handle gracefully)
    const closeModal = document.getElementById("close-modal");
    const closePasswordModal = document.getElementById("close-password-modal");
    const cancelEdit = document.getElementById("cancel-edit");
    const cancelPassword = document.getElementById("cancel-password");
    const confirmCancel = document.getElementById("confirm-cancel");
    const confirmNo = document.getElementById("confirm-no");
    const confirmOk = document.getElementById("confirm-ok");

    // Get forms (these may not exist in HTML, so we'll handle gracefully)
    const editForm = document.getElementById("edit-form");
    const passwordForm = document.getElementById("password-form");

    // Profile data elements
    const adminName = document.getElementById("admin-name");
    const adminEmail = document.getElementById("admin-email");
    const adminPhone = document.getElementById("admin-phone");
    const adminUsername = document.getElementById("admin-username");
    // PROFILE PICTURE: commented out for now — re-enable later
    // const profilePic = document.getElementById("profile-pic");

    // Form input elements (these may not exist in HTML, so we'll handle gracefully)
    const editName = document.getElementById("edit-name");
    const editEmail = document.getElementById("edit-email");
    const editPhone = document.getElementById("edit-phone");
    const editUsername = document.getElementById("edit-username");

    // History elements
    const historyList = document.getElementById("history-list");
    const historyEmpty = document.getElementById("history-empty");
    const historyFilter = document.getElementById("history-filter");
    const totalActivitiesCount = document.getElementById("total-activities");
    const todayActivitiesCount = document.getElementById("today-activities");
    const thisWeekActivitiesCount = document.getElementById(
        "this-week-activities"
    );

    // History system variables
    let adminHistory = [];
    let displayedHistoryCount = 0;
    const ITEMS_PER_PAGE = 10;
    let currentFilter = "all";
    let confirmCallback = null;

    // PROFILE PICTURE: commented out for now — re-enable later
    // const availableProfilePictures = [
    //     { id: "boy.png", name: "Default Admin", src: "/images/boy.png" },
    //     { id: "boy1.png", name: "Professional", src: "/images/boy1.png" },
    //     { id: "boy2.png", name: "Casual", src: "/images/boy2.png" },
    //     { id: "girl.png", name: "Formal", src: "/images/girl.png" },
    //     { id: "girl1.png", name: "Modern", src: "/images/girl1.png" },
    //     { id: "girl2.png", name: "Classic", src: "/images/gril2.png" },
    // ];

    // Initialize history system
    initializeHistory();

    // History Management Functions
    async function initializeHistory() {
        try {
            const res = await fetch(
                `/admin/activities?filter=${currentFilter}`
            );
            const data = await res.json();

            if (Array.isArray(data)) {
                adminHistory = data.map((entry) => ({
                    ...entry,
                    type: mapActivityType(entry.activity_type),
                    action: entry.activity_type,
                    details: entry.description,
                    timestamp: new Date(entry.created_at),
                    date: new Date(entry.created_at).toLocaleDateString(),
                    time: new Date(entry.created_at).toLocaleTimeString(),
                }));

                displayedHistoryCount = 0;
                displayHistory();
                updateHistoryStats();
            } else {
                console.error(
                    "Error fetching admin history:",
                    data.error || data
                );
            }
        } catch (error) {
            console.error("Failed to fetch admin history", error);
        }
    }

    function mapActivityType(activityType) {
        if (!activityType) return "system";
        const type = activityType.toLowerCase();

        if (type.includes("login") || type.includes("logout")) return "login";
        if (type.includes("profile")) return "profile";
        if (type.includes("security")) return "security";
        return "system";
    }

    function formatTypeForBackend(type, action) {
        if (type === "login")
            return action.includes("logout") ? "Logout" : "Login";
        if (type === "profile") return "Profile Change";
        if (type === "security") return "Security";
        return "System Action";
    }

    function addToHistory(type, action, details = "") {
        const historyEntry = {
            id: generateId(),
            type: type, // login, profile, system, security
            action: action,
            details: details,
            timestamp: new Date(),
            date: new Date().toLocaleDateString(),
            time: new Date().toLocaleTimeString(),
        };

        adminHistory.unshift(historyEntry);

        if (adminHistory.length > 100) {
            adminHistory = adminHistory.slice(0, 100);
        }

        displayHistory();
        updateHistoryStats();

        // Persist to backend
        fetch("/admin/activities/store", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify({
                activity_type: formatTypeForBackend(type, action),
                description: details,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    console.error("Failed to store activity log", data);
                }
            })
            .catch((err) => {
                console.error("Error sending activity log to server", err);
            });
    }

    function filterHistory() {
        if (currentFilter === "all") {
            return adminHistory;
        }
        return adminHistory.filter((entry) => entry.type === currentFilter);
    }

    function displayHistory() {
        const filteredHistory = filterHistory();
        const historyToShow = filteredHistory.slice(
            0,
            displayedHistoryCount + ITEMS_PER_PAGE
        );

        historyList.innerHTML = "";

        if (historyToShow.length === 0) {
            historyEmpty.style.display = "block";
            loadMoreBtn.style.display = "none";
            return;
        }

        historyEmpty.style.display = "none";

        historyToShow.forEach((entry) => {
            const li = document.createElement("li");
            li.innerHTML = `
                <div class="history-content">
                    <div class="history-action">${entry.action}</div>
                    <div class="history-details">${entry.details}</div>
                </div>
                <div class="history-time">
                    <div>${entry.date}</div>
                    <div>${entry.time}</div>
                </div>
            `;
            historyList.appendChild(li);
        });

        displayedHistoryCount = historyToShow.length;

        if (filteredHistory.length > displayedHistoryCount) {
            loadMoreBtn.style.display = "block";
        } else {
            loadMoreBtn.style.display = "none";
        }
    }

    function updateHistoryStats() {
        const total = adminHistory.length;
        const today = adminHistory.filter((entry) => {
            const entryDate = new Date(entry.timestamp).toDateString();
            const todayDate = new Date().toDateString();
            return entryDate === todayDate;
        }).length;

        const thisWeek = adminHistory.filter((entry) => {
            const entryDate = new Date(entry.timestamp);
            const weekAgo = new Date();
            weekAgo.setDate(weekAgo.getDate() - 7);
            return entryDate >= weekAgo;
        }).length;

        if (totalActivitiesCount) totalActivitiesCount.textContent = total;
        if (todayActivitiesCount) todayActivitiesCount.textContent = today;
        if (thisWeekActivitiesCount)
            thisWeekActivitiesCount.textContent = thisWeek;
    }

    function clearHistory() {
        showConfirmModal(
            "Clear History",
            "Are you sure you want to clear all history? This action cannot be undone.",
            () => {
                adminHistory = [];
                displayedHistoryCount = 0;
                displayHistory();
                updateHistoryStats();
                showSuccessModal("History cleared successfully!");
                addToHistory(
                    "system",
                    "History cleared",
                    "All admin history entries were deleted"
                );
            }
        );
    }

    // PROFILE PICTURE: commented out for now — re-enable later
    // function createImageSelectionModal() {
    //     const existingModal = document.getElementById("image-selection-modal");
    //     if (existingModal) {
    //         console.log("Image selection modal already exists");
    //         return existingModal;
    //     }
    //     console.log("Creating new image selection modal");
    //     const modalHTML = `...`; // image selection modal HTML
    //     document.body.insertAdjacentHTML("beforeend", modalHTML);
    //     const newImageModal = document.getElementById("image-selection-modal");
    //     // ... event listeners and image grid setup
    //     return newImageModal;
    // }

    // Event Listeners
    if (historyFilter) {
        historyFilter.addEventListener("change", function () {
            currentFilter = this.value;
            initializeHistory(); // re-fetch from server
        });
    }

    if (clearHistoryBtn) {
        clearHistoryBtn.addEventListener("click", clearHistory);
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", function () {
            displayHistory();
        });
    }

    // PROFILE PICTURE: commented out for now — re-enable later
    // if (changePicBtn) {
    //     changePicBtn.addEventListener("click", function (e) {
    //         e.preventDefault();
    //         const imageModal = createImageSelectionModal();
    //         if (imageModal) {
    //             imageModal.style.display = "block";
    //             addToHistory("system", "Profile picture selection opened", "Opened image selection modal");
    //         }
    //     });
    // }
    // if (profilePicInput) {
    //     profilePicInput.addEventListener("change", function (e) {
    //         const file = e.target.files[0];
    //         if (file) {
    //             const reader = new FileReader();
    //             reader.onload = function (e) {
    //                 if (profilePic) {
    //                     profilePic.src = e.target.result;
    //                     addToHistory("profile", "Profile picture updated", "Changed admin profile picture via file upload");
    //                     showSuccessModal("Profile picture updated successfully!");
    //                 }
    //             };
    //             reader.readAsDataURL(file);
    //         }
    //     });
    // }

    // Edit Profile Button Click
    if (editBtn && editModal) {
        editBtn.addEventListener("click", function () {
            // Pre-fill form with current data
            if (editName && adminName) editName.value = adminName.textContent;
            if (editEmail && adminEmail)
                editEmail.value = adminEmail.textContent;
            if (editPhone && adminPhone)
                editPhone.value = adminPhone.textContent;
            if (editUsername && adminUsername)
                editUsername.value = adminUsername.textContent;

            editModal.style.display = "flex";
            editModal.classList.add('open');
            addToHistory(
                "system",
                "Edit profile form opened",
                "Started editing admin profile information"
            );
        });
    }

    // Change Password Link Click
    if (changePasswordLink && passwordModal) {
        changePasswordLink.addEventListener("click", function (e) {
            e.preventDefault();
            // Reset to step 1
            resetPasswordModal();
            passwordModal.style.display = "flex";
            passwordModal.classList.add('open');
            addToHistory(
                "security",
                "Password change initiated",
                "Opened password change form"
            );
        });
    }

    // Helper to reset the password modal to step 1
    function resetPasswordModal() {
        const step1 = document.getElementById("password-step-1");
        const step2 = document.getElementById("password-step-2");
        const currentPw = document.getElementById("current-password");
        const resetCode = document.getElementById("reset-code");
        const newPw = document.getElementById("new-password");
        const confirmPw = document.getElementById("confirm-password");
        const step1Msg = document.getElementById("step-1-message");

        if (step1) step1.style.display = "block";
        if (step2) step2.style.display = "none";
        if (currentPw) currentPw.value = "";
        if (resetCode) resetCode.value = "";
        if (newPw) newPw.value = "";
        if (confirmPw) confirmPw.value = "";
        if (step1Msg) {
            step1Msg.style.display = "none";
            step1Msg.textContent = "";
            step1Msg.className = "info-message";
        }
        // Reset strength bar & checklist
        const sBar = document.getElementById("strength-bar");
        const sText = document.getElementById("strength-text");
        if (sBar) { sBar.style.width = "0%"; sBar.style.backgroundColor = "#dc3545"; }
        if (sText) sText.textContent = "";
        ["req-length","req-uppercase","req-lowercase","req-number","req-special"].forEach(id => {
            const el = document.getElementById(id);
            if (el) { el.style.color = "#999"; el.textContent = "✗ " + el.textContent.replace(/^[✓✗]\s*/, ""); }
        });
    }

    // Close Modal Functions - Only add listeners if elements exist
    function closeProfileModals() {
        const modals = [editModal, passwordModal, successModal, confirmModal];
        modals.forEach(m => {
            if (m) {
                m.style.display = "none";
                m.classList.remove('open');
            }
        });
    }

    if (closeModal && editModal) {
        closeModal.addEventListener("click", closeProfileModals);
    }

    if (closePasswordModal && passwordModal) {
        closePasswordModal.addEventListener("click", closeProfileModals);
    }

    if (cancelEdit && editModal) {
        cancelEdit.addEventListener("click", function () {
            editModal.style.display = "none";
            editModal.classList.remove('open');
            addToHistory(
                "system",
                "Profile edit cancelled",
                "Cancelled profile editing without saving"
            );
        });
    }

    if (cancelPassword && passwordModal) {
        cancelPassword.addEventListener("click", function () {
            passwordModal.style.display = "none";
            passwordModal.classList.remove('open');
            addToHistory(
                "security",
                "Password change cancelled",
                "Cancelled password change without saving"
            );
        });
    }

    if (successOkBtn && successModal) {
        successOkBtn.addEventListener("click", function () {
            successModal.style.display = "none";
            successModal.classList.remove('open');
        });
    }

    if (confirmCancel && confirmModal) {
        confirmCancel.addEventListener("click", function () {
            confirmModal.style.display = "none";
            confirmModal.classList.remove('open');
            confirmCallback = null;
        });
    }

    if (confirmNo && confirmModal) {
        confirmNo.addEventListener("click", function () {
            confirmModal.style.display = "none";
            confirmModal.classList.remove('open');
            confirmCallback = null;
        });
    }

    if (confirmOk && confirmModal) {
        confirmOk.addEventListener("click", function () {
            if (confirmCallback) {
                confirmCallback();
            }
            confirmModal.style.display = "none";
            confirmModal.classList.remove('open');
            confirmCallback = null;
        });
    }

    // Close modal when clicking outside
    window.addEventListener("click", function (event) {
        if (editModal && event.target === editModal) {
            editModal.style.display = "none";
            editModal.classList.remove('open');
        }
        if (passwordModal && event.target === passwordModal) {
            passwordModal.style.display = "none";
            passwordModal.classList.remove('open');
        }
        if (successModal && event.target === successModal) {
            successModal.style.display = "none";
            successModal.classList.remove('open');
        }
        if (confirmModal && event.target === confirmModal) {
            confirmModal.style.display = "none";
            confirmModal.classList.remove('open');
            confirmCallback = null;
        }

        // PROFILE PICTURE: commented out for now — re-enable later
        // const imageModal = document.getElementById("image-selection-modal");
        // if (imageModal && event.target === imageModal) {
        //     imageModal.style.display = "none";
        // }
    });

    // Edit Profile Form Submit
    if (editForm) {
        editForm.addEventListener("submit", function (e) {
            e.preventDefault();

            // Store old values for comparison
            const oldName = adminName ? adminName.textContent : "";
            const oldEmail = adminEmail ? adminEmail.textContent : "";
            const oldPhone = adminPhone ? adminPhone.textContent : "";
            const oldUsername = adminUsername ? adminUsername.textContent : "";

            // Update profile information
            if (adminName && editName) adminName.textContent = editName.value;
            if (adminEmail && editEmail)
                adminEmail.textContent = editEmail.value;
            if (adminPhone && editPhone)
                adminPhone.textContent = editPhone.value;
            if (adminUsername && editUsername)
                adminUsername.textContent = editUsername.value;

            // Close modal
            if (editModal) {
                editModal.style.display = "none";
                editModal.classList.remove('open');
            }

            // Track specific changes
            let changes = [];
            let changeDetails = [];

            if (editName && oldName !== editName.value) {
                changes.push("name");
                changeDetails.push(`Name: "${oldName}" → "${editName.value}"`);
            }
            if (editEmail && oldEmail !== editEmail.value) {
                changes.push("email");
                changeDetails.push(
                    `Email: "${oldEmail}" → "${editEmail.value}"`
                );
            }
            if (editPhone && oldPhone !== editPhone.value) {
                changes.push("phone");
                changeDetails.push(
                    `Phone: "${oldPhone}" → "${editPhone.value}"`
                );
            }
            if (editUsername && oldUsername !== editUsername.value) {
                changes.push("username");
                changeDetails.push(
                    `Username: "${oldUsername}" → "${editUsername.value}"`
                );
            }

            // Add specific activity
            if (changes.length > 0) {
                const changeText = `Profile updated: ${changes.join(", ")}`;
                const detailText = changeDetails.join("; ");
                addToHistory("profile", changeText, detailText);
            } else {
                addToHistory(
                    "profile",
                    "Profile form submitted",
                    "No changes were made to profile information"
                );
            }

            // Send to backend
            fetch("/admin/profile/update", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    name: editName.value,
                    email: editEmail.value,
                    phone: editPhone.value,
                    username: editUsername.value,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        showSuccessModal(data.message);
                    } else {
                        showErrorMessage(
                            data.message || "Update failed",
                            editModal
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error updating profile:", error);
                    showErrorMessage(
                        "An error occurred while updating profile.",
                        editModal
                    );
                });
        });
    }

    // ===== PASSWORD CHANGE - TWO STEP FLOW =====

    const sendCodeBtn = document.getElementById("send-code-btn");
    const verifyCodeBtn = document.getElementById("verify-code-btn");
    const backToStep1Btn = document.getElementById("back-to-step1");
    const showPwFields = document.getElementById("show-password-fields");

    // Step 1: Send verification code to email
    if (sendCodeBtn) {
        sendCodeBtn.addEventListener("click", function () {
            const currentPassword = document.getElementById("current-password");
            if (!currentPassword || currentPassword.value === "") {
                showErrorMessage("Please enter your current password.", passwordModal);
                return;
            }

            const btn = sendCodeBtn;
            const originalText = btn.textContent;
            btn.textContent = "Sending...";
            btn.disabled = true;

            fetch("/admin/profile/send-reset-code", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    current_password: currentPassword.value,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    btn.textContent = originalText;
                    btn.disabled = false;

                    if (data.success) {
                        // Show step 2
                        document.getElementById("password-step-1").style.display = "none";
                        document.getElementById("password-step-2").style.display = "block";
                        addToHistory(
                            "security",
                            "Verification code sent",
                            "Password reset code sent to admin email"
                        );
                    } else {
                        if (data.errors) {
                            let errorMessage = "";
                            Object.values(data.errors).forEach((errors) => {
                                errorMessage += errors.join(", ") + " ";
                            });
                            showErrorMessage(errorMessage.trim(), passwordModal);
                        } else {
                            showErrorMessage(data.message || "Failed to send code.", passwordModal);
                        }
                        addToHistory("security", "Password reset code failed", data.message || "Unknown error");
                    }
                })
                .catch((err) => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    console.error("Send code error", err);
                    showErrorMessage("An error occurred while sending the code.", passwordModal);
                });
        });
    }

    // Step 2: Verify code and change password
    if (verifyCodeBtn) {
        verifyCodeBtn.addEventListener("click", function () {
            const currentPassword = document.getElementById("current-password");
            const resetCode = document.getElementById("reset-code");
            const newPassword = document.getElementById("new-password");
            const confirmPassword = document.getElementById("confirm-password");

            if (!resetCode || resetCode.value.trim() === "") {
                showErrorMessage("Please enter the verification code sent to your email.", passwordModal);
                return;
            }

            if (resetCode.value.trim().length !== 6) {
                showErrorMessage("Verification code must be 6 digits.", passwordModal);
                return;
            }

            if (!newPassword || newPassword.value === "") {
                showErrorMessage("Please enter a new password.", passwordModal);
                return;
            }

            if (newPassword.value.length < 8) {
                showErrorMessage("New password must be at least 8 characters long.", passwordModal);
                return;
            }

            if (newPassword.value !== confirmPassword.value) {
                showErrorMessage("New passwords do not match!", passwordModal);
                addToHistory("security", "Password change failed", "Password confirmation mismatch");
                return;
            }

            const btn = verifyCodeBtn;
            const originalText = btn.textContent;
            btn.textContent = "Changing...";
            btn.disabled = true;

            fetch("/admin/profile/change-password", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify({
                    current_password: currentPassword ? currentPassword.value : "",
                    reset_code: resetCode.value.trim(),
                    new_password: newPassword.value,
                    new_password_confirmation: confirmPassword.value,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    btn.textContent = originalText;
                    btn.disabled = false;

                    if (data.success) {
                        passwordModal.style.display = "none";
                        passwordModal.classList.remove('open');
                        resetPasswordModal();
                        showSuccessModal(data.message);
                        addToHistory("security", "Password changed successfully", "Admin account password updated");
                    } else {
                        if (data.errors) {
                            let errorMessage = "";
                            Object.values(data.errors).forEach((errors) => {
                                errorMessage += errors.join(", ") + " ";
                            });
                            showErrorMessage(errorMessage.trim(), passwordModal);
                        } else {
                            showErrorMessage(data.message || "Password change failed.", passwordModal);
                        }
                        addToHistory("security", "Password change failed", data.message || "Unknown error");
                    }
                })
                .catch((err) => {
                    btn.textContent = originalText;
                    btn.disabled = false;
                    console.error("Password update error", err);
                    showErrorMessage("An error occurred while changing password.", passwordModal);
                    addToHistory("security", "Password change error", "Network or server error occurred");
                });
        });
    }

    // ── Real-time Password Strength & Requirements Checklist ──
    const newPwInput = document.getElementById("new-password");
    const strengthBar = document.getElementById("strength-bar");
    const strengthText = document.getElementById("strength-text");

    const reqs = {
      length: document.getElementById("req-length"),
      uppercase: document.getElementById("req-uppercase"),
      lowercase: document.getElementById("req-lowercase"),
      number: document.getElementById("req-number"),
      special: document.getElementById("req-special"),
    };

    function updatePasswordStrength(val) {
      let score = 0;

      // Length checks
      if (val.length >= 8) score += 1;
      if (val.length >= 12) score += 1;

      // Complexity checks
      if (/[A-Z]/.test(val)) score += 1;
      if (/[a-z]/.test(val)) score += 1;
      if (/\d/.test(val)) score += 1;
      if (/[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/.test(val)) score += 1;

      // Update requirement checklist
      if (reqs.length) {
        const ok = val.length >= 8;
        reqs.length.style.color = ok ? "#28a745" : "#999";
        reqs.length.textContent = (ok ? "✓" : "✗") + " At least 8 characters";
      }
      if (reqs.uppercase) {
        const ok = /[A-Z]/.test(val);
        reqs.uppercase.style.color = ok ? "#28a745" : "#999";
        reqs.uppercase.textContent = (ok ? "✓" : "✗") + " At least 1 uppercase letter";
      }
      if (reqs.lowercase) {
        const ok = /[a-z]/.test(val);
        reqs.lowercase.style.color = ok ? "#28a745" : "#999";
        reqs.lowercase.textContent = (ok ? "✓" : "✗") + " At least 1 lowercase letter";
      }
      if (reqs.number) {
        const ok = /\d/.test(val);
        reqs.number.style.color = ok ? "#28a745" : "#999";
        reqs.number.textContent = (ok ? "✓" : "✗") + " At least 1 number";
      }
      if (reqs.special) {
        const ok = /[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]/.test(val);
        reqs.special.style.color = ok ? "#28a745" : "#999";
        reqs.special.textContent = (ok ? "✓" : "✗") + " At least 1 special character";
      }

      // Map score to strength level
      const levels = [
        { label: "Very Weak", color: "#dc3545", width: "10%" },
        { label: "Weak",      color: "#dc3545", width: "25%" },
        { label: "Fair",      color: "#ffc107", width: "45%" },
        { label: "Good",      color: "#17a2b8", width: "65%" },
        { label: "Strong",    color: "#28a745", width: "85%" },
        { label: "Very Strong", color: "#28a745", width: "100%" },
      ];

      const idx = Math.min(score, levels.length - 1);
      if (strengthBar) {
        strengthBar.style.width = levels[idx].width;
        strengthBar.style.backgroundColor = levels[idx].color;
      }
      if (strengthText) {
        strengthText.textContent = val.length > 0 ? levels[idx].label : "";
        strengthText.style.color = levels[idx].color;
      }
    }

    if (newPwInput) {
      newPwInput.addEventListener("input", function () {
        updatePasswordStrength(this.value);
      });
    }

    // Back button: go from step 2 to step 1
    if (backToStep1Btn) {
        backToStep1Btn.addEventListener("click", function () {
            document.getElementById("password-step-2").style.display = "none";
            document.getElementById("password-step-1").style.display = "block";
            // Clear step 2 fields
            const resetCode = document.getElementById("reset-code");
            const newPw = document.getElementById("new-password");
            const confirmPw = document.getElementById("confirm-password");
            if (resetCode) resetCode.value = "";
            if (confirmPw) confirmPw.value = "";
            // Reset strength UI
            if (strengthBar) { strengthBar.style.width = "0%"; strengthBar.style.backgroundColor = "#dc3545"; }
            if (strengthText) strengthText.textContent = "";
            Object.values(reqs).forEach(el => {
              if (el) { el.style.color = "#999"; el.textContent = el.textContent.replace(/^[✓✗]/, "✗"); }
            });
        });
    }

    // Toggle show passwords on step 2
    if (showPwFields) {
        showPwFields.addEventListener("change", function () {
            const newPw = document.getElementById("new-password");
            const confirmPw = document.getElementById("confirm-password");
            const type = this.checked ? "text" : "password";
            if (newPw) newPw.type = type;
            if (confirmPw) confirmPw.type = type;
        });
    }

    // End of password change two-step flow

    // Helper function to show success modal
    function showSuccessModal(message) {
        const successMessageText = document.getElementById(
            "success-message-text"
        );
        if (successMessageText) {
            successMessageText.textContent = message;
        }
        if (successModal) {
            successModal.style.display = "block";
        }
    }

    // Helper function to show confirm modal
    function showConfirmModal(title, message, callback) {
        if (!confirmModal) {
            console.error("Confirm modal not found");
            return;
        }

        const confirmTitle = confirmModal.querySelector("h3");
        const confirmMessage = document.getElementById("confirm-message");

        if (confirmTitle) confirmTitle.textContent = title;
        if (confirmMessage) confirmMessage.textContent = message;

        confirmCallback = callback;
        confirmModal.style.display = "block";
    }

    // Helper function to show error message
    function showErrorMessage(message, modal) {
        if (!modal) return;

        // Remove existing error messages
        const existingError = modal.querySelector(".error-message");
        if (existingError) {
            existingError.remove();
        }

        // Create error message element
        const errorDiv = document.createElement("div");
        errorDiv.className = "error-message";
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 10px 0;
            display: block;
        `;

        const modalContent = modal.querySelector(".modal-content");
        const header = modalContent.querySelector("h3");

        if (modalContent) {
            if (header) {
                header.insertAdjacentElement("afterend", errorDiv);
            } else {
                modalContent.appendChild(errorDiv);
            }
        }

        setTimeout(function () {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    // Phone number formatting - Fixed to allow editing of all digits
    const phoneInput = document.getElementById("edit-phone");
    if (phoneInput) {
        phoneInput.addEventListener("input", function () {
            let value = this.value.replace(/\D/g, ""); // Remove non-digits

            // Limit to 11 digits (like 09123456789)
            if (value.length > 11) {
                value = value.substring(0, 11);
            }

            // Format based on length
            if (value.length >= 4 && value.length <= 7) {
                value = value.replace(/(\d{4})(\d{0,3})/, "$1-$2");
            } else if (value.length > 7) {
                value = value.replace(/(\d{4})(\d{3})(\d{0,4})/, "$1-$2-$3");
            }

            this.value = value;
        });
    }

    // Form validation for real-time feedback
    const inputs = document.querySelectorAll(
        'input[type="text"], input[type="email"], input[type="password"]'
    );
    inputs.forEach((input) => {
        input.addEventListener("blur", function () {
            validateInput(this);
        });
    });

    function validateInput(input) {
        const value = input.value.trim();

        // Remove existing error styling
        input.style.borderColor = "#ccc";

        // Validate based on input type
        if (input.type === "email" && value !== "") {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                input.style.borderColor = "#dc3545";
                addToHistory(
                    "system",
                    "Validation error",
                    `Invalid email format entered: ${value}`
                );
            }
        }

        if (input.name === "phone" && value !== "") {
            const phoneRegex = /^\d{4}-\d{3}-\d{4}$/;
            if (
                !phoneRegex.test(value) &&
                value.replace(/\D/g, "").length > 0
            ) {
                input.style.borderColor = "#dc3545";
                addToHistory(
                    "system",
                    "Validation error",
                    `Invalid phone format entered: ${value}`
                );
            }
        }

        if (input.type === "password" && value !== "" && value.length < 6) {
            input.style.borderColor = "#dc3545";
            addToHistory(
                "security",
                "Validation error",
                "Password too short during input"
            );
        }
    }

    // Update history timestamps every minute
    setInterval(function () {
        // Update relative timestamps
        adminHistory.forEach((entry) => {
            const now = new Date();
            const entryTime = new Date(entry.timestamp);
            const diffMs = now - entryTime;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) {
                entry.relativeTime = "Just now";
            } else if (diffMins < 60) {
                entry.relativeTime = `${diffMins} min${
                    diffMins > 1 ? "s" : ""
                } ago`;
            } else if (diffHours < 24) {
                entry.relativeTime = `${diffHours} hour${
                    diffHours > 1 ? "s" : ""
                } ago`;
            } else {
                entry.relativeTime = `${diffDays} day${
                    diffDays > 1 ? "s" : ""
                } ago`;
            }
        });

        // Re-display history with updated timestamps if needed
        if (adminHistory.length > 0) {
            displayHistory();
        }
    }, 60000); // Update every minute

    // Export history functionality (for future use)
        // Add export button dynamically (optional)
    const exportBtn = document.createElement("button");
    exportBtn.textContent = "Export";
    exportBtn.className = "export-btn";
    exportBtn.style.cssText = `
        background: #28a745;
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 5px;
        font-size: 12px;
        cursor: pointer;
        margin-left: 5px;
    `;
    exportBtn.addEventListener("click", exportHistory);

    // Add export button to history controls
    const historyControls = document.querySelector(".history-controls");
    if (historyControls) {
        historyControls.appendChild(exportBtn);
    }

    console.log(
        "Admin Profile with Enhanced History System loaded successfully!"
    );
});