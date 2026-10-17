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
                window.vsNotify("Please fill in all required fields.", "warning");
                event.preventDefault();
                return;
            }

            // Check if time slot is required and selected
            if (period && !timeSlot) {
                window.vsNotify("Please select a time slot.", "warning");
                event.preventDefault();
                return;
            }

            // Validate venue selection
            const venueSelect = document.getElementById("field_venue");
            if (
                !venueSelect?.value
            ) {
                window.vsNotify("Please specify the venue.", "warning");
                event.preventDefault();
                return;
            }

            // Validate event type selection
            const eventTypeSelect = document.getElementById("field_event_type");
            if (
                !eventTypeSelect?.value
            ) {
                window.vsNotify("Please specify the event type.", "warning");
                event.preventDefault();
                return;
            }

            // Validate theme/motif selection
            const themeMotifSelect = document.getElementById("field_theme_motif");
            if (
                !themeMotifSelect?.value
            ) {
                window.vsNotify("Please specify the theme/motif.", "warning");
                event.preventDefault();
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                window.vsNotify("Please enter a valid email address.", "warning");
                event.preventDefault();
                return;
            }

            // Past dates are deliberately not blocked here. Admins may record an
            // event that already happened; the server applies that rule.

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
    const statusModalNotice = document.getElementById("statusModalNotice");
    const statusReasonGroup = document.getElementById("statusReasonGroup");
    const statusReasonInput = document.getElementById("statusReason");
    const statusConfirmGroup = document.getElementById("statusConfirmGroup");
    const statusConfirmCheck = document.getElementById("statusConfirmCheck");
    const statusConfirmLabel = document.getElementById("statusConfirmLabel");

    // Mirrors App\Support\AvailabilityOverrideRule::MIN_REASON_LENGTH.
    const MIN_CLOSE_REASON_LENGTH = 5;

    function showStatusNotice(message) {
        if (!statusModalNotice) return;
        statusModalNotice.textContent = message;
        statusModalNotice.hidden = false;
    }

    function clearStatusNotice() {
        if (!statusModalNotice) return;
        statusModalNotice.textContent = "";
        statusModalNotice.hidden = true;
    }

    // Closing a date that still holds reservations has to be deliberate: the
    // admin sees the number of reservations being kept, writes a reason, and
    // ticks the confirmation box. The server enforces the same three things.
    function syncCloseFields() {
        const closingWithBookings =
            statusSelect?.value === "Closed" &&
            selectedDate !== null &&
            (dateStatuses[selectedDate]?.booked ?? 0) > 0;

        if (statusReasonGroup) statusReasonGroup.hidden = !closingWithBookings;
        if (statusConfirmGroup) statusConfirmGroup.hidden = !closingWithBookings;

        if (!closingWithBookings) {
            if (statusReasonInput) statusReasonInput.value = "";
            if (statusConfirmCheck) statusConfirmCheck.checked = false;
            clearStatusNotice();
            return;
        }

        const count = dateStatuses[selectedDate]?.booked ?? 0;

        if (statusConfirmLabel) {
            statusConfirmLabel.textContent = `I understand this date holds ${count} active reservation(s) and closing it will not cancel them.`;
        }

        showStatusNotice(
            `This date holds ${count} booking(s). Closing it stops new bookings only. Add a reason and confirm to continue.`
        );
    }

    const calendarContainer = document.querySelector(
        ".vs-admin-reservation .calendar-container"
    );
    const maxEvents = Number.parseInt(calendarContainer?.dataset.maxEvents, 10) || 4;
    // "Today" comes from the server (computed for Manila on this page) so past
    // dates are marked consistently without using the browser clock.
    const todayKey =
        calendarContainer?.dataset.today || new Date().toISOString().slice(0, 10);

    let currentDate = new Date(`${todayKey}T00:00:00`);
    let selectedDate = null;
    let dateStatuses = {};

    // Latest /calendar/availability payload, used by the venue panel.
    let availabilitySnapshot = null;

    // Availability states: only the colour class lives here. Every word (the
    // state labels, the cue words) and the daily cap come from the server, so
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
    let dailyCap = 0;
    let slotLabels = {
        Available: "available",
        Half: "limited",
        Nearly: "nearly full",
        Full: "full",
        Closed: "closed",
    };
    let cueWords = { Full: "Full", Closed: "Closed" };

    // First paint uses the cap the server already rendered; the payload
    // refreshes it in case config changed.
    dailyCap = maxEvents;

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
                accessibleLabel += `, ${entry?.booked ?? 0} of ${dailyCap} events booked`;
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
                syncCloseFields();
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
    // day's events are taken. Days with nothing booked stay unlabelled so the
    // calendar is not covered in "0/4".
    function cueText(state, booked) {
        if (state === "Full" || state === "Closed") {
            return cueWords[state] || state;
        }

        return booked > 0 ? `${booked}/${dailyCap}` : "";
    }

    // Modal event handlers
    if (statusSelect) {
        statusSelect.addEventListener("change", syncCloseFields);
    }

    // Repaints one day cell from the local state map.
    function refreshDayCell(dateKey, entry) {
        document.querySelectorAll(".calendar-day[data-date]").forEach((cell) => {
            if (cell.getAttribute("data-date") === dateKey) {
                applyStatusStyle(cell, entry || null);
            }
        });
    }

    if (saveStatusBtn) {
        saveStatusBtn.addEventListener("click", function () {
            if (!selectedDate || !statusSelect) {
                return;
            }

            clearStatusNotice();

            const selectedStatus = statusSelect.value;

            // Empty selection means "no status": the override row is removed.
            if (!selectedStatus) {
                delete dateStatuses[selectedDate];
                refreshDayCell(selectedDate, null);
                deleteStatusFromBackend(selectedDate);

                if (modal) {
                    closeStatusModal();
                }

                return;
            }

            const booked = dateStatuses[selectedDate]?.booked ?? 0;
            const closingWithBookings = selectedStatus === "Closed" && booked > 0;

            if (closingWithBookings) {
                const reason = (statusReasonInput?.value || "").trim();

                if (reason.length < MIN_CLOSE_REASON_LENGTH) {
                    showStatusNotice(
                        `A reason of at least ${MIN_CLOSE_REASON_LENGTH} characters is required to close a date that still has bookings.`
                    );
                    statusReasonInput?.focus();
                    return;
                }

                if (statusConfirmCheck && !statusConfirmCheck.checked) {
                    showStatusNotice(
                        `Tick the confirmation box to close this date. It holds ${booked} active reservation(s).`
                    );
                    statusConfirmCheck.focus();
                    return;
                }
            }

            // The cell is only repainted once the server accepts the override,
            // so a refused save cannot leave the calendar showing a state the
            // database does not have.
            saveStatusToBackend(selectedDate, selectedStatus, {
                reason: closingWithBookings ? (statusReasonInput?.value || "").trim() : null,
                confirmedCount: closingWithBookings ? booked : null,
            });
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
    // the period, and keeps occupied venues out of the Venue dropdown. It
    // renders from the calendar payload when that has loaded; otherwise it
    // falls back to the venue-availability endpoint.
    const venuePanel = initVenueAvailability({
        dateInput: document.getElementById("field_date"),
        periodSelect,
        venueSelect: document.getElementById("field_venue"),
        panel: document.getElementById("venueAvailability"),
        getSnapshot: () => availabilitySnapshot,
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
                if (Number.isFinite(data?.daily_cap)) dailyCap = data.daily_cap;
                if (data?.labels) slotLabels = data.labels;
                if (data?.cue_words) cueWords = data.cue_words;

                // Replace, so a slot that frees up stops showing as occupied.
                Object.keys(dateStatuses).forEach((date) => delete dateStatuses[date]);
                Object.entries(data?.dates || {}).forEach(([date, entry]) => {
                    dateStatuses[date] = entry;
                });

                availabilitySnapshot = data;
                venuePanel?.refresh();
                renderCalendar();
            })
            .catch((error) => {
                console.error("Error loading availability:", error);
                // Still render calendar even if data loading fails
                renderCalendar();
            })
            .finally(() => {
                calendar?.classList.remove("is-loading");
                calendar?.setAttribute("aria-busy", "false");
            });
    }

    // Save status to backend. Every save is an override, so `override` is
    // always sent: the date then follows the admin's choice instead of the
    // computed slot count, until the override is removed.
    function saveStatusToBackend(date, status, options = {}) {
        const payload = { date: date, status: status, override: true };

        if (options.reason) {
            payload.reason = options.reason;
        }

        if (options.confirmedCount !== null && options.confirmedCount !== undefined) {
            payload.confirmed_count = options.confirmedCount;
        }

        return fetch("/admin/availability", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") ||
                    document.querySelector('input[name="_token"]')?.value,
            },
            body: JSON.stringify(payload),
        })
            .then((response) =>
                response.json().then((data) => ({ ok: response.ok, data }))
            )
            .then(({ ok, data }) => {
                if (!ok || !data?.success) {
                    // The modal stays open with the server's explanation so the
                    // admin can fix the reason or the confirmation.
                    showStatusNotice(
                        data?.message || "The override could not be saved."
                    );
                    return;
                }

                dateStatuses[date] = {
                    state: status,
                    booked: data.booked ?? dateStatuses[date]?.booked ?? 0,
                };
                refreshDayCell(date, dateStatuses[date]);

                showNotification("Status updated successfully", "success");

                if (modal) {
                    closeStatusModal();
                }
            })
            .catch((error) => {
                console.error("Error saving status:", error);
                showStatusNotice("Error updating status. Please try again.");
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

    // Initialize calendar. Draw the grid at once so the page never shows an
    // empty calendar while /calendar/availability is in flight; the payload
    // repaints it with the real states as soon as it arrives.
    calendar?.classList.add("is-loading");
    calendar?.setAttribute("aria-busy", "true");
    renderCalendar();
    loadAvailabilityData();

    // ---------------------------- //
    //      UTILITY FUNCTIONS      //
    // ---------------------------- //

    // Show notification function (shared confirmation modal)
    function showNotification(message, type = "info") {
        window.vsNotify(message, type);
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
