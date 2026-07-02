document.addEventListener("DOMContentLoaded", function () {
    // Form logic for 'Others' fields
    const venueSelect = document.getElementById("venue");
    const otherVenueInput = document.getElementById("otherVenue");

    const themeMotifSelect = document.getElementById("theme_motif");
    const otherThemeMotifInput = document.getElementById("otherThemeMotif");

    const eventTypeSelect = document.getElementById("event_type");
    const otherEventTypeInput = document.getElementById("otherEventType");

    const form = document.querySelector("form");

    function toggleOtherInput(selectElement, otherInput, otherValue) {
        if (selectElement && otherInput) {
            otherInput.style.display =
                selectElement.value === otherValue ? "block" : "none";
            otherInput.required = selectElement.value === otherValue;
            if (selectElement.value !== otherValue) {
                otherInput.value = "";
            }
        }
    }

    // Add event listeners and initialize state for "Others" fields
    if (venueSelect && otherVenueInput) {
        venueSelect.addEventListener("change", () =>
            toggleOtherInput(venueSelect, otherVenueInput, "Others")
        );
        toggleOtherInput(venueSelect, otherVenueInput, "Others");
    }

    if (themeMotifSelect && otherThemeMotifInput) {
        themeMotifSelect.addEventListener("change", () =>
            toggleOtherInput(themeMotifSelect, otherThemeMotifInput, "Others")
        );
        toggleOtherInput(themeMotifSelect, otherThemeMotifInput, "Others");
    }

    if (eventTypeSelect && otherEventTypeInput) {
        eventTypeSelect.addEventListener("change", () =>
            toggleOtherInput(eventTypeSelect, otherEventTypeInput, "Others")
        );
        toggleOtherInput(eventTypeSelect, otherEventTypeInput, "Others");
    }

    // Form validation
    if (form) {
        form.addEventListener("submit", function (event) {
            const name = document.getElementById("name")?.value.trim();
            const email = document.getElementById("email")?.value.trim();
            const contact = document.getElementById("contact")?.value.trim();
            const message = document.getElementById("message")?.value.trim();
            const date = document.getElementById("date")?.value.trim();
            const period = document.getElementById("period")?.value.trim();
            const timeSlot = document.getElementById("time_slot")?.value.trim();

            // Check required fields
            if (!name || !email || !contact || !message || !date || !period) {
                alert("Please fill in all required fields.");
                event.preventDefault();
                return;
            }

            // Check if time slot is required and selected
            if (period && !timeSlot) {
                alert("Please select a time slot.");
                event.preventDefault();
                return;
            }

            // Validate venue selection
            if (
                !venueSelect.value ||
                (venueSelect.value === "Others" &&
                    !otherVenueInput.value.trim())
            ) {
                alert("Please specify the venue.");
                event.preventDefault();
                return;
            }

            // Validate event type selection
            if (
                !eventTypeSelect.value ||
                (eventTypeSelect.value === "Others" &&
                    !otherEventTypeInput.value.trim())
            ) {
                alert("Please specify the event type.");
                event.preventDefault();
                return;
            }

            // Validate theme/motif selection
            if (
                !themeMotifSelect.value ||
                (themeMotifSelect.value === "Others" &&
                    !otherThemeMotifInput.value.trim())
            ) {
                alert("Please specify the theme/motif.");
                event.preventDefault();
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert("Please enter a valid email address.");
                event.preventDefault();
                return;
            }

            // Date validation - no past dates
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                alert("Please select a date from today onwards.");
                event.preventDefault();
                return;
            }

            // Check if selected date is available
            if (
                dateStatuses[date] === "full" ||
                dateStatuses[date] === "closed"
            ) {
                alert(
                    "The selected date is not available. Please choose another date."
                );
                event.preventDefault();
                return;
            }

            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";
            }
        });
    }

    // ---------------------------- //
    //       CALENDAR FUNCTION     //
    // ---------------------------- //
    const calendar = document.getElementById("calendar");
    const monthYear = document.getElementById("month-year");
    const prevMonthBtn = document.getElementById("prevMonth");
    const nextMonthBtn = document.getElementById("nextMonth");
    const modal = document.getElementById("statusModal");
    const saveStatusBtn = document.getElementById("saveStatus");
    const closeModalBtn = document.getElementById("closeModal");
    const statusSelect = document.getElementById("statusSelect");
    const undoStatusBtn = document.getElementById("undoStatus");

    let currentDate = new Date();
    let selectedDate = null;
    let dateStatuses = {};

    function renderCalendar() {
        if (!calendar || !monthYear) return;

        console.log(dateStatuses);

        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();
        monthYear.textContent = new Intl.DateTimeFormat("en-US", {
            month: "long",
            year: "numeric",
        }).format(currentDate);

        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        calendar.innerHTML = "";

        // Add day headers
        const daysOfWeek = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        daysOfWeek.forEach((day) => {
            const dayHeader = document.createElement("div");
            dayHeader.classList.add("calendar-day", "day-header");
            dayHeader.textContent = day;
            calendar.appendChild(dayHeader);
        });

        // Add empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement("div");
            emptyCell.classList.add("calendar-day", "empty");
            calendar.appendChild(emptyCell);
        }

        // Add days of the month
        for (let day = 1; day <= lastDate; day++) {
            const dayCell = document.createElement("div");
            dayCell.classList.add("calendar-day");
            dayCell.textContent = day;

            // Format date as YYYY-M-D to match backend format
            const paddedMonth = String(month + 1).padStart(2, "0");
            const paddedDay = String(day).padStart(2, "0");
            const dateKey = `${year}-${paddedMonth}-${paddedDay}`;
            // const dateKey = `${year}-${month + 1}-${day}`;
            dayCell.setAttribute("data-date", dateKey);

            // Apply status if it exists
            if (dateStatuses[dateKey]) {
                console.log(dateStatuses[dateKey]);
                applyStatusStyle(dayCell, dateStatuses[dateKey]);
            }

            // Add click handler for admin functionality
            dayCell.addEventListener("click", function (e) {
                e.preventDefault();
                selectedDate = dateKey;
                if (statusSelect) {
                    statusSelect.value = dateStatuses[dateKey] || "";
                }
                if (modal) {
                    modal.style.display = "flex";
                }
            });

            calendar.appendChild(dayCell);
        }
    }

    function applyStatusStyle(cell, status) {
        cell.classList.remove("Available", "Half", "Nearly", "Full", "Closed");
        cell.style.background = "";
        cell.style.color = "";

        switch (status) {
            case "Available":
                cell.style.background = "green";
                cell.style.color = "white";
                break;
            case "Half":
                cell.style.background = "gold";
                cell.style.color = "black";
                break;
            case "Nearly":
                cell.style.background = "orange";
                cell.style.color = "white";
                break;
            case "Full":
                cell.style.background = "red";
                cell.style.color = "white";
                break;
            case "Closed":
                cell.style.background = "gray";
                cell.style.color = "white";
                break;
        }
    }

    // Modal event handlers
    if (saveStatusBtn) {
        saveStatusBtn.addEventListener("click", function () {
            if (selectedDate && statusSelect) {
                const selectedStatus = statusSelect.value;

                if (selectedStatus) {
                    dateStatuses[selectedDate] = selectedStatus;
                    saveStatusToBackend(selectedDate, selectedStatus);
                } else {
                    delete dateStatuses[selectedDate];
                    deleteStatusFromBackend(selectedDate);
                }

                // Update the visual appearance
                const dayCells = document.querySelectorAll(
                    ".calendar-day[data-date]"
                );
                dayCells.forEach((cell) => {
                    if (cell.getAttribute("data-date") === selectedDate) {
                        applyStatusStyle(cell, selectedStatus);
                    }
                });

                if (modal) {
                    modal.style.display = "none";
                }
            }
        });
    }

    if (undoStatusBtn) {
        undoStatusBtn.addEventListener("click", function () {
            if (selectedDate && dateStatuses[selectedDate]) {
                delete dateStatuses[selectedDate];

                const dayCells = document.querySelectorAll(
                    ".calendar-day[data-date]"
                );
                dayCells.forEach((cell) => {
                    if (cell.getAttribute("data-date") === selectedDate) {
                        applyStatusStyle(cell, null);
                    }
                });

                deleteStatusFromBackend(selectedDate);

                if (modal) {
                    modal.style.display = "none";
                }
            }
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", function () {
            if (modal) {
                modal.style.display = "none";
            }
        });
    }

    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // Navigation event handlers
    if (prevMonthBtn) {
        prevMonthBtn.addEventListener("click", function () {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener("click", function () {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });
    }

    // ---------------------------- //
    //   AM/PM → Time Slot Filter   //
    // ---------------------------- //
    const periodSelect = document.getElementById("period");
    const timeSlotWrapper = document.getElementById("timeSlotWrapper");
    const timeSlotSelect = document.getElementById("time_slot");

    const timeSlots = {
        AM: ["9am–11am", "10am–12pm", "11am–1pm"],
        PM: ["2pm–4pm", "3pm–5pm", "4pm–6pm"],
    };

    if (periodSelect && timeSlotSelect && timeSlotWrapper) {
        // Initially hide and disable time slot
        timeSlotWrapper.style.display = "none";
        timeSlotSelect.disabled = true;
        timeSlotSelect.required = false;

        periodSelect.addEventListener("change", function () {
            const selectedPeriod = this.value;

            // Reset options
            timeSlotSelect.innerHTML =
                '<option value="">Select a time slot</option>';

            if (timeSlots[selectedPeriod]) {
                // Populate time slots
                timeSlots[selectedPeriod].forEach((slot) => {
                    const option = document.createElement("option");
                    option.value = slot;
                    option.textContent = slot;
                    timeSlotSelect.appendChild(option);
                });

                // Enable and show the time slot dropdown
                timeSlotSelect.disabled = false;
                timeSlotWrapper.style.display = "block";
                timeSlotSelect.required = true;
            } else {
                // Hide and disable if no valid period
                timeSlotSelect.disabled = false;
                timeSlotSelect.required = true;
                timeSlotWrapper.style.display = "block";
            }
        });
    }

    // Load availability data from backend
    function loadAvailabilityData() {
        fetch("/calendar/availability")
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((dateStatusMap) => {
                if (dateStatusMap && typeof dateStatusMap === "object") {
                    Object.entries(dateStatusMap).forEach(([date, status]) => {
                        console.log(date, status);
                        dateStatuses[date] = status;
                    });
                }
                renderCalendar();
            })
            .catch((error) => {
                console.error("Error loading availability:", error);
                // Still render calendar even if data loading fails
                renderCalendar();
            });
    }

    // Save status to backend
    function saveStatusToBackend(date, status) {
        fetch("/admin/availability", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") ||
                    document.querySelector('input[name="_token"]')?.value,
            },
            body: JSON.stringify({
                date: date,
                status: status,
            }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Status saved:", data);
                showNotification("Status updated successfully", "success");
            })
            .catch((error) => {
                console.error("Error saving status:", error);
                showNotification("Error updating status", "error");
            });
    }

    // Delete status from backend
    function deleteStatusFromBackend(date) {
        fetch("/admin/availability", {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") ||
                    document.querySelector('input[name="_token"]')?.value,
            },
            body: JSON.stringify({
                date: date,
            }),
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Status deleted:", data);
                showNotification("Status removed successfully", "success");
            })
            .catch((error) => {
                console.error("Error deleting status:", error);
                showNotification("Error removing status", "error");
            });
    }

    // Initialize calendar
    loadAvailabilityData();

    // ---------------------------- //
    //      UTILITY FUNCTIONS      //
    // ---------------------------- //

    // Show notification function
    function showNotification(message, type = "info") {
        const notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.textContent = message;

        // Add to body
        document.body.appendChild(notification);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    // Date formatting utility
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    // Phone number formatting
    const contactInput = document.getElementById("contact");
    if (contactInput) {
        contactInput.addEventListener("input", function (e) {
            let value = e.target.value.replace(/\D/g, "");
            if (value.length >= 10) {
                value = value.substring(0, 10);
                e.target.value = value.replace(
                    /(\d{3})(\d{3})(\d{4})/,
                    "($1) $2-$3"
                );
            }
        });
    }

    // Auto-save form data to localStorage for user convenience
    const formInputs = document.querySelectorAll(
        'input[type="text"], input[type="email"], textarea, select'
    );
    formInputs.forEach((input) => {
        // Load saved data
        const savedValue = localStorage.getItem(`form_${input.id}`);
        if (savedValue && input.type !== "date") {
            input.value = savedValue;
        }

        // Save data on change
        input.addEventListener("change", function () {
            localStorage.setItem(`form_${input.id}`, input.value);
        });
    });

    // Clear saved form data on successful submission
    if (form) {
        form.addEventListener("submit", function () {
            // Clear saved data after a delay to allow form processing
            setTimeout(() => {
                formInputs.forEach((input) => {
                    localStorage.removeItem(`form_${input.id}`);
                });
            }, 1000);
        });
    }

    // Add keyboard navigation for calendar
    document.addEventListener("keydown", function (e) {
        if (modal && modal.style.display === "flex") {
            if (e.key === "Escape") {
                modal.style.display = "none";
            }
        }
    });

    // Refresh calendar data periodically (every 5 minutes)
    setInterval(loadAvailabilityData, 300000);

    console.log("Event management system initialized successfully");

    const dateUnavailableModal = document.getElementById(
        "dateUnavailableModal"
    );
    const closeUnavailableModal = document.getElementById(
        "closeUnavailableModal"
    );

    let dateInput = document.getElementById("date");

    dateInput?.addEventListener("change", () => {
        const selected = dateInput.value;
        if (
            dateStatuses[selected] === "Full" ||
            dateStatuses[selected] === "Closed"
        ) {
            // alert("This date is not available. Please choose another.");
            dateUnavailableModal.style.display = "block";
            dateInput.value = "";
        }
    });

    closeUnavailableModal?.addEventListener("click", () => {
        dateUnavailableModal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
        if (event.target === dateUnavailableModal) {
            dateUnavailableModal.style.display = "none";
        }
    });
});
