import { initVenueAvailability } from "../venue-availability";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");

    // Form validation
    if (form) {
        form.addEventListener("submit", function (event) {
            const name = document.getElementById("field_name")?.value.trim();
            const email = document.getElementById("field_email")?.value.trim();
            const contact = document.getElementById("field_contact_number")?.value.trim();
            const message = document.getElementById("field_message")?.value.trim();
            const date = document.getElementById("field_date")?.value.trim();
            const period = document.getElementById("field_period")?.value.trim();
            const timeSlot = document.getElementById("field_time")?.value.trim();

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
            const venueSelect = document.getElementById("field_venue");
            if (
                !venueSelect?.value
            ) {
                alert("Please specify the venue.");
                event.preventDefault();
                return;
            }

            // Validate event type selection
            const eventTypeSelect = document.getElementById("field_event_type");
            if (
                !eventTypeSelect?.value
            ) {
                alert("Please specify the event type.");
                event.preventDefault();
                return;
            }

            // Validate theme/motif selection
            const themeMotifSelect = document.getElementById("field_theme_motif");
            if (
                !themeMotifSelect?.value
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
    function closeStatusModal() {
        if (modal) {
            window.vsModalCloseAfterFade(modal, () => {
                modal.style.display = "none";
            });
        }
    }
    const saveStatusBtn = document.getElementById("saveStatus");
    const closeModalBtn = document.getElementById("closeModal");
    const statusSelect = document.getElementById("statusSelect");
    const undoStatusBtn = document.getElementById("undoStatus");

    const calendarContainer = document.querySelector(
        ".vs-admin-reservation .calendar-container"
    );
    const maxSlots = Number.parseInt(calendarContainer?.dataset.maxSlots, 10) || 4;
    // "Today" comes from the server (computed for Manila on this page) so past
    // dates are marked consistently without using the browser clock.
    const todayKey =
        calendarContainer?.dataset.today || new Date().toISOString().slice(0, 10);

    let currentDate = new Date(`${todayKey}T00:00:00`);
    let selectedDate = null;
    let dateStatuses = {};

    // Availability states: only the colour class lives here. Every word (the
    // state labels, the cue words) and the slot total come from the server, so
    // config/availability.php stays the single source of truth.
    const AVAILABILITY_STATE_CLASSES = {
        Available: "vs-calendar-day--success",
        Half: "vs-calendar-day--half",
        Nearly: "vs-calendar-day--nearly",
        Full: "vs-calendar-day--danger",
        Closed: "vs-calendar-day--neutral",
    };
    const AVAILABILITY_CLASSES = Object.values(AVAILABILITY_STATE_CLASSES);

    // Defaults, replaced by the /calendar/availability payload.
    let slotTotal = 0;
    let slotLabels = {
        Available: "available",
        Half: "limited",
        Nearly: "nearly full",
        Full: "full",
        Closed: "closed",
    };
    let cueWords = { Full: "Full", Closed: "Closed" };

    // First paint uses the slot total the server already rendered; the payload
    // refreshes it in case the venue list changed.
    slotTotal = maxSlots;

    function formatLongDate(dateKey) {
        const [year, month, day] = dateKey.split("-").map(Number);
        return new Intl.DateTimeFormat("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        }).format(new Date(Date.UTC(year, month - 1, day)));
    }

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

            const number = document.createElement("span");
            number.className = "calendar-day__number";
            number.textContent = day;
            dayCell.appendChild(number);

            // Format date as YYYY-MM-DD to match backend format
            const paddedMonth = String(month + 1).padStart(2, "0");
            const paddedDay = String(day).padStart(2, "0");
            const dateKey = `${year}-${paddedMonth}-${paddedDay}`;
            dayCell.setAttribute("data-date", dateKey);
            if (dateKey === todayKey) {
                dayCell.classList.add("vs-calendar-day--today");
            }
            if (dateKey === selectedDate) dayCell.classList.add("vs-calendar-day--selected");

            // Past dates stay selectable here; they are only visually muted.
            if (dateKey < todayKey) {
                dayCell.classList.add("is-past");
            }

            // Apply status (0 booked when there is none) plus the text cue.
            const entry = dateStatuses[dateKey];
            const state = applyStatusStyle(dayCell, entry);
            const longDate = formatLongDate(dateKey);
            let accessibleLabel = `${longDate}, ${slotLabels[state] || state}`;
            if (state !== "Closed") {
                accessibleLabel += `, ${entry?.booked ?? 0} of ${slotTotal} slots booked`;
            }
            dayCell.setAttribute("aria-label", accessibleLabel);

            // Add click handler for admin functionality
            dayCell.addEventListener("click", function (e) {
                e.preventDefault();
                selectedDate = dateKey;
                calendar.querySelectorAll(".vs-calendar-day--selected").forEach((cell) => cell.classList.remove("vs-calendar-day--selected"));
                dayCell.classList.add("vs-calendar-day--selected");
                if (statusSelect) {
                    statusSelect.value = dateStatuses[dateKey]?.state || "";
                }
                if (modal) {
                    window.vsModalPrepareOpen(modal);
                    modal.style.display = "flex";
                }
            });

            calendar.appendChild(dayCell);
        }
    }

    // Applies the availability colour and the text cue, and returns the state
    // name so the caller can build the accessible label. A day with no entry is
    // 0 booked and therefore Available.
    function applyStatusStyle(cell, entry) {
        cell.classList.remove(...AVAILABILITY_CLASSES);
        cell.querySelector(".calendar-day__cue")?.remove();

        const state = AVAILABILITY_STATE_CLASSES[entry?.state]
            ? entry.state
            : "Available";
        cell.classList.add(AVAILABILITY_STATE_CLASSES[state]);

        const cue = cueText(state, entry?.booked ?? 0);
        if (cue) {
            const cueEl = document.createElement("span");
            cueEl.className = "calendar-day__cue";
            cueEl.setAttribute("aria-hidden", "true");
            cueEl.textContent = cue;
            cell.appendChild(cueEl);
        }

        return state;
    }

    // Full and Closed show a word; every other state shows how many of the
    // venue-period slots are taken. Days with nothing booked stay unlabelled so
    // the calendar is not covered in "0/8".
    function cueText(state, booked) {
        if (state === "Full" || state === "Closed") {
            return cueWords[state] || state;
        }

        return booked > 0 ? `${booked}/${slotTotal}` : "";
    }

    // Modal event handlers
    if (saveStatusBtn) {
        saveStatusBtn.addEventListener("click", function () {
            if (selectedDate && statusSelect) {
                const selectedStatus = statusSelect.value;

                if (selectedStatus) {
                    dateStatuses[selectedDate] = {
                        state: selectedStatus,
                        booked: dateStatuses[selectedDate]?.booked ?? 0,
                    };
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
                        applyStatusStyle(cell, dateStatuses[selectedDate] || null);
                    }
                });

                if (modal) {
                    closeStatusModal();
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
                    closeStatusModal();
                }
            }
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener("click", function () {
            if (modal) {
                closeStatusModal();
            }
        });
    }

    // Close modal when clicking outside
    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) {
                closeStatusModal();
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
    const periodSelect = document.getElementById("field_period");
    const timeSlotSelect = document.getElementById("field_time");
    const timeSlotWrapper = timeSlotSelect?.closest(".form-group");

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
                timeSlotSelect.disabled = true;
                timeSlotSelect.required = false;
                timeSlotWrapper.style.display = "none";
            }
        });
    }

    // "Venues on <date>" panel below the calendar. Refreshes with the date and
    // the period, and keeps occupied venues out of the Venue dropdown.
    initVenueAvailability({
        dateInput: document.getElementById("field_date"),
        periodSelect,
        venueSelect: document.getElementById("field_venue"),
        panel: document.getElementById("venueAvailability"),
    });

    // Load availability data from backend
    function loadAvailabilityData() {
        fetch("/calendar/availability")
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (Number.isFinite(data?.slots_total)) slotTotal = data.slots_total;
                if (data?.labels) slotLabels = data.labels;
                if (data?.cue_words) cueWords = data.cue_words;

                // Replace, so a slot that frees up stops showing as occupied.
                Object.keys(dateStatuses).forEach((date) => delete dateStatuses[date]);
                Object.entries(data?.dates || {}).forEach(([date, entry]) => {
                    dateStatuses[date] = entry;
                });

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
        fetch("/admin/availability/remove-override", {
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

    // Reset form fields on page load (prevents browser bfcache from showing stale data)
    if (form) {
        form.reset();
        // Clear any leftover auto-saved form data from previous sessions
        document.querySelectorAll('.dynamic-form [id]').forEach(el => {
            localStorage.removeItem(`form_${el.id}`);
        });
    }

    // Add keyboard navigation for calendar
    document.addEventListener("keydown", function (e) {
        if (modal && modal.style.display === "flex") {
            if (e.key === "Escape") {
                closeStatusModal();
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
            dateStatuses[selected]?.state === "Full" ||
            dateStatuses[selected]?.state === "Closed"
        ) {
            // alert("This date is not available. Please choose another.");
            window.vsModalPrepareOpen(dateUnavailableModal);
            dateUnavailableModal.style.display = "flex";
            dateUnavailableModal.classList.add('open');
            dateInput.value = "";
        }
    });

    closeUnavailableModal?.addEventListener("click", () => {
        window.vsModalCloseAfterFade(dateUnavailableModal, () => {
            dateUnavailableModal.style.display = "none";
            dateUnavailableModal.classList.remove('open');
        });
    });

    window.addEventListener("click", function (event) {
        if (event.target === dateUnavailableModal) {
            window.vsModalCloseAfterFade(dateUnavailableModal, () => {
                dateUnavailableModal.style.display = "none";
                dateUnavailableModal.classList.remove('open');
            });
        }
    });
});
