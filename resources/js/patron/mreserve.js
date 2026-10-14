// resources/js/patron/mreserve.js

import "../bootstrap";
import { initVenueAvailability } from "../venue-availability";

document.addEventListener("DOMContentLoaded", function () {
    const venueSelect = document.getElementById("field_venue");
    const otherVenueInput =
        document.getElementById("otherVenue") ||
        document.getElementById("other_venue") ||
        document.getElementById("field_other_venue");

    const themeMotifSelect = document.getElementById("field_theme_motif");
    const otherThemeMotifInput =
        document.getElementById("otherThemeMotif") ||
        document.getElementById("other_theme_motif") ||
        document.getElementById("field_other_theme_motif");

    const eventTypeSelect = document.getElementById("field_event_type");
    const otherEventTypeInput =
        document.getElementById("otherEventType") ||
        document.getElementById("other_event_type") ||
        document.getElementById("field_other_event_type");

    const form = document.querySelector("form");
    // Reset form on page load (prevents browser bfcache from restoring stale data)
    if (form) form.reset();

    const dateInput = document.getElementById("field_date");

    const periodSelect = document.getElementById("field_period");
    const timeSlotSelect = document.getElementById("field_time");
    // The dynamic form wraps each field in a .form-group div
    const timeSlotWrapper = timeSlotSelect?.closest(".form-group");

    const timeSlots = {
        AM: ["9am–1pm", "10am–2pm", "11am–3pm"],
        PM: ["4pm-8pm", "5pm–9pm", "6pm–10pm"],
    };

    const dateStatuses = {};

    // Availability states: the color class, the text cue shown under the day
    // number, and the word used in the accessible label. 0/4 and 1/4 are both
    // "Available" (green); "Half" (2 booked) is the 2/4 Limited range.
    const AVAILABILITY_STATES = {
        Available: {
            className: "vs-calendar-day--success",
            cue: "1/4",
            label: "available",
            booked: 1,
        },
        Half: {
            className: "vs-calendar-day--half",
            cue: "2/4",
            label: "limited",
            booked: 2,
        },
        Nearly: {
            className: "vs-calendar-day--nearly",
            cue: "3/4",
            label: "nearly full",
            booked: 3,
        },
        Full: {
            className: "vs-calendar-day--danger",
            cue: "Full",
            label: "full",
            booked: 4,
        },
        Closed: {
            className: "vs-calendar-day--neutral",
            cue: "Closed",
            label: "closed",
            booked: 0,
        },
    };
    const FREE_STATE = {
        className: "vs-calendar-day--success",
        cue: "",
        label: "available",
        booked: 0,
    };
    const AVAILABILITY_CLASSES = [
        "vs-calendar-day--success",
        "vs-calendar-day--half",
        "vs-calendar-day--nearly",
        "vs-calendar-day--danger",
        "vs-calendar-day--neutral",
    ];

    function toggleOtherInput(selectEl, inputEl) {
        if (!selectEl || !inputEl) return;
        inputEl.style.display = selectEl.value === "Others" ? "block" : "none";
        inputEl.required = selectEl.value === "Others";
        if (selectEl.value !== "Others") inputEl.value = "";
    }

    [
        [venueSelect, otherVenueInput],
        [themeMotifSelect, otherThemeMotifInput],
        [eventTypeSelect, otherEventTypeInput],
    ].forEach(([select, input]) => {
        select?.addEventListener("change", () =>
            toggleOtherInput(select, input)
        );
        toggleOtherInput(select, input);
    });

    periodSelect?.addEventListener("change", function () {
        const selectedPeriod = this.value;
        timeSlotSelect.innerHTML =
            '<option value="">Select a time slot</option>';
        if (timeSlots[selectedPeriod]) {
            timeSlots[selectedPeriod].forEach((slot) => {
                const option = document.createElement("option");
                option.value = slot;
                option.textContent = slot;
                timeSlotSelect.appendChild(option);
            });
            timeSlotWrapper.style.display = "block";
            timeSlotSelect.disabled = false;
        } else {
            timeSlotWrapper.style.display = "none";
            timeSlotSelect.disabled = true;
        }
    });

    // "Venues on <date>" panel below the calendar. Refreshes with the date and
    // the period, and keeps occupied venues out of the Venue dropdown.
    initVenueAvailability({
        dateInput,
        periodSelect,
        venueSelect,
        panel: document.getElementById("venueAvailability"),
    });

    // Agreement Modal Logic
    const agreementModal = document.getElementById("agreementModal");
    const agreementContent = document.getElementById("agreementContent");
    const agreeButton = document.getElementById("agreeButton");
    let hasScrolledToBottom = false;

    if (agreementModal && agreementContent && agreeButton) {
        const shouldShowAgreement = !window.guestAgreementAccepted;

        if (shouldShowAgreement) {
            setTimeout(() => {
                window.vsModalPrepareOpen(agreementModal);
                agreementModal.style.display = "flex";
                agreementModal.classList.add("show");
                form?.classList.add("form-disabled");
                document.body.style.overflow = "hidden";
            }, 100);
        } else {
            agreementModal.style.display = "none";
            agreementModal.classList.remove("show");
            form?.classList.remove("form-disabled");
            document.body.style.overflow = "auto";
        }

        if (shouldShowAgreement) {
            setTimeout(() => {
                const { scrollHeight, clientHeight } = agreementContent;
                if (scrollHeight <= clientHeight + 10) {
                    hasScrolledToBottom = true;
                    agreeButton.disabled = false;
                }
            }, 300);
        }

        agreementContent.addEventListener("scroll", () => {
            const { scrollTop, scrollHeight, clientHeight } = agreementContent;
            if (scrollTop + clientHeight >= scrollHeight - 10) {
                hasScrolledToBottom = true;
                agreeButton.disabled = false;
            }
        });

        agreeButton.addEventListener("click", () => {
            if (hasScrolledToBottom) {
                fetch(window.agreeUrl, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                        Accept: "application/json",
                        "Content-Type": "application/json",
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({ accepted: true }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            window.guestAgreementAccepted = true;
                            window.vsModalCloseAfterFade(agreementModal, () => {
                                agreementModal.style.display = "none";
                                agreementModal.classList.remove("show");
                                form?.classList.remove("form-disabled");
                                document.body.style.overflow = "auto";
                            });
                        }
                    })
                    .catch((error) => {
                        console.error("Failed to save guest consent:", error);
                    });
            }
        });
    }

    // Calendar Rendering
    const calendar = document.getElementById("calendar");
    const monthYear = document.getElementById("month-year");
    const prevMonthBtn = document.getElementById("prevMonth");
    const nextMonthBtn = document.getElementById("nextMonth");

    const calendarContainer = document.querySelector(
        ".vs-patron-reservation .calendar-container"
    );
    const maxSlots =
        Number.parseInt(calendarContainer?.dataset.maxSlots, 10) || 4;
    // "Today" comes from the server (computed for Manila on this page) so it
    // never follows the visitor's browser clock. The browser value is only a
    // fallback for the unlikely case where the attribute is missing.
    const todayKey =
        calendarContainer?.dataset.today || new Date().toISOString().slice(0, 10);

    let currentDate = new Date(`${todayKey}T00:00:00`);

    function formatLongDate(dateKey) {
        const [year, month, day] = dateKey.split("-").map(Number);
        return new Intl.DateTimeFormat("en-US", {
            month: "long",
            day: "numeric",
            year: "numeric",
        }).format(new Date(Date.UTC(year, month - 1, day)));
    }

    function renderCalendar() {
        const month = currentDate.getMonth();
        const year = currentDate.getFullYear();

        if (!calendar || !monthYear) return;

        monthYear.textContent = new Intl.DateTimeFormat("en-US", {
            month: "long",
            year: "numeric",
        }).format(currentDate);

        const firstDay = new Date(year, month, 1).getDay();
        const lastDate = new Date(year, month + 1, 0).getDate();

        calendar.innerHTML = "";

        ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach((day) => {
            const dayHeader = document.createElement("div");
            dayHeader.classList.add("calendar-day", "day-header");
            dayHeader.textContent = day;
            dayHeader.setAttribute("aria-hidden", "true");
            calendar.appendChild(dayHeader);
        });

        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement("div");
            emptyCell.classList.add("calendar-day", "empty");
            emptyCell.setAttribute("aria-hidden", "true");
            calendar.appendChild(emptyCell);
        }

        for (let day = 1; day <= lastDate; day++) {
            const paddedMonth = String(month + 1).padStart(2, "0");
            const paddedDay = String(day).padStart(2, "0");
            const dateKey = `${year}-${paddedMonth}-${paddedDay}`;

            const cell = document.createElement("button");
            cell.type = "button";
            cell.classList.add("calendar-day", "vs-calendar-btn");
            cell.setAttribute("data-date", dateKey);

            const number = document.createElement("span");
            number.className = "calendar-day__number";
            number.textContent = day;
            cell.appendChild(number);

            const status = dateStatuses[dateKey];
            const longDate = formatLongDate(dateKey);

            if (dateKey < todayKey) {
                // Past dates: aria-disabled, skipped in tab order, never selectable.
                cell.classList.add("is-past");
                cell.setAttribute("aria-disabled", "true");
                cell.tabIndex = -1;
                cell.setAttribute(
                    "aria-label",
                    `${longDate}, unavailable, this date has passed`
                );
            } else {
                if (dateKey === todayKey) {
                    cell.classList.add("vs-calendar-day--today");
                }

                const state = applyStatusStyle(cell, status);

                let accessibleLabel = `${longDate}, ${state.label}`;
                if (status && status !== "Closed") {
                    accessibleLabel += `, ${state.booked} of ${maxSlots} slots booked`;
                }
                cell.setAttribute("aria-label", accessibleLabel);

                if (dateKey === dateInput?.value) {
                    cell.classList.add("vs-calendar-day--selected");
                }
            }

            calendar.appendChild(cell);
        }
    }

    // Clicking or keyboard-activating a day fills the Date field and highlights it.
    calendar?.addEventListener("click", (event) => {
        const cell = event.target.closest(".calendar-day[data-date]");
        if (!cell || cell.classList.contains("is-past") || !dateInput) return;

        const dateKey = cell.getAttribute("data-date");
        if (!dateKey) return;

        dateInput.value = dateKey;
        // Programmatic assignment does not emit "change", so fire the events the
        // rest of the page listens to (unavailable-date modal, selected highlight).
        dateInput.dispatchEvent(new Event("input", { bubbles: true }));
        dateInput.dispatchEvent(new Event("change", { bubbles: true }));
    });

    // Applies the availability color and the text cue, and returns the state so
    // the caller can build the accessible label. Days with no data are 0/4.
    function applyStatusStyle(cell, status) {
        cell.classList.remove(...AVAILABILITY_CLASSES);
        cell.querySelector(".calendar-day__cue")?.remove();

        const state = AVAILABILITY_STATES[status] || FREE_STATE;
        cell.classList.add(state.className);

        if (state.cue) {
            const cue = document.createElement("span");
            cue.className = "calendar-day__cue";
            cue.setAttribute("aria-hidden", "true");
            cue.textContent = state.cue;
            cell.appendChild(cue);
        }

        return state;
    }

    prevMonthBtn?.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthBtn?.addEventListener("click", () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    function loadAvailabilityData() {
        fetch("/calendar/availability")
            .then((res) => res.json())
            .then((data) => {
                Object.entries(data).forEach(([date, status]) => {
                    dateStatuses[date] = status;
                });
                renderCalendar();
            })
            .catch((err) => {
                console.error("Failed to load availability:", err);
                renderCalendar();
            });
    }

    loadAvailabilityData();

    const dateUnavailableModal = document.getElementById(
        "dateUnavailableModal"
    );
    const closeUnavailableModal = document.getElementById(
        "closeUnavailableModal"
    );

    let selectedUnavailableDate = null;

    dateInput?.addEventListener("change", () => {
        const selected = dateInput.value;
        const status = dateStatuses[selected];

        if (status === "Full" || status === "Closed") {
            selectedUnavailableDate = selected;

            const title = document.getElementById("unavailableTitle");
            const message = document.getElementById("unavailableMessage");
            const waitlistOption = document.getElementById("waitlistOption");

            if (status === "Closed") {
                title.textContent = "Date Closed";
                message.textContent = "Sorry, this date is closed. Please choose another available date.";
                waitlistOption.style.display = "none";
            } else {
                title.textContent = "Date Full";
                message.textContent = "This date is fully booked. Join the waitlist to be notified if a slot opens up!";
                waitlistOption.style.display = "block";
                document.getElementById("waitlistName").value = "";
                document.getElementById("waitlistEmail").value = "";
                document.getElementById("waitlistMessage").style.display = "none";
            }

            window.vsModalPrepareOpen(dateUnavailableModal);
            dateUnavailableModal.style.display = "block";
            dateInput.value = "";
        }

        document.querySelectorAll("#calendar .calendar-day[data-date]").forEach((cell) => {
            cell.classList.toggle("vs-calendar-day--selected", cell.getAttribute("data-date") === dateInput.value);
        });
    });

    // Waitlist join handler
    document.getElementById("joinWaitlistBtn")?.addEventListener("click", function () {
        const name = document.getElementById("waitlistName").value.trim();
        const email = document.getElementById("waitlistEmail").value.trim();
        const msgEl = document.getElementById("waitlistMessage");

        if (!name) {
            msgEl.textContent = "Please enter your name.";
            msgEl.classList.remove("vs-state--neutral");
            msgEl.classList.add("vs-state--error");
            msgEl.style.display = "block";
            return;
        }

        if (!email || !email.includes("@")) {
            msgEl.textContent = "Please enter a valid email address.";
            msgEl.classList.remove("vs-state--neutral");
            msgEl.classList.add("vs-state--error");
            msgEl.style.display = "block";
            return;
        }

        msgEl.textContent = "Joining waitlist...";
        msgEl.classList.remove("vs-state--error");
        msgEl.classList.add("vs-state--neutral");
        msgEl.style.display = "block";
        this.disabled = true;

        fetch("/waitlist/join", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
                "Content-Type": "application/json",
                Accept: "application/json",
            },
            body: JSON.stringify({
                patron_name: name,
                patron_email: email,
                date: selectedUnavailableDate,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                this.disabled = false;
                if (data.success) {
                    const waitlistSuccessModal = document.getElementById("waitlistSuccessModal");
                    window.vsModalCloseAfterFade(dateUnavailableModal, () => {
                        dateUnavailableModal.style.display = "none";
                        window.vsModalPrepareOpen(waitlistSuccessModal);
                        waitlistSuccessModal.style.display = "block";
                    });
                } else {
                    msgEl.textContent = data.message || "Failed to join waitlist. Please try again.";
                    msgEl.classList.remove("vs-state--neutral");
                    msgEl.classList.add("vs-state--error");
                    msgEl.style.display = "block";
                }
            })
            .catch((err) => {
                this.disabled = false;
                msgEl.textContent = "An error occurred. Please try again.";
                msgEl.classList.remove("vs-state--neutral");
                msgEl.classList.add("vs-state--error");
                msgEl.style.display = "block";
                console.error("Waitlist join error:", err);
            });
    });

    closeUnavailableModal?.addEventListener("click", () => {
        window.vsModalCloseAfterFade(dateUnavailableModal, () => {
            dateUnavailableModal.style.display = "none";
        });
    });

    document.getElementById("closeWaitlistSuccessModal")?.addEventListener("click", () => {
        const waitlistSuccessModal = document.getElementById("waitlistSuccessModal");
        window.vsModalCloseAfterFade(waitlistSuccessModal, () => {
            waitlistSuccessModal.style.display = "none";
        });
    });

    window.addEventListener("click", function (event) {
        if (event.target === dateUnavailableModal) {
            window.vsModalCloseAfterFade(dateUnavailableModal, () => {
                dateUnavailableModal.style.display = "none";
            });
        }
        const waitlistSuccessModal = document.getElementById("waitlistSuccessModal");
        if (event.target === waitlistSuccessModal) {
            window.vsModalCloseAfterFade(waitlistSuccessModal, () => {
                waitlistSuccessModal.style.display = "none";
            });
        }
    });
});
