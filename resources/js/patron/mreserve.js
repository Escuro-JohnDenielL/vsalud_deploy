// resources/js/patron/mreserve.js

import "../bootstrap";

document.addEventListener("DOMContentLoaded", function () {
    const venueSelect = document.getElementById("venue");
    const otherVenueInput =
        document.getElementById("otherVenue") ||
        document.getElementById("other_venue");

    const themeMotifSelect = document.getElementById("theme_motif");
    const otherThemeMotifInput =
        document.getElementById("otherThemeMotif") ||
        document.getElementById("other_theme_motif");

    const eventTypeSelect = document.getElementById("event_type");
    const otherEventTypeInput =
        document.getElementById("otherEventType") ||
        document.getElementById("other_event_type");

    const form = document.querySelector("form");
    const dateInput = document.getElementById("date");

    const periodSelect = document.getElementById("period");
    const timeSlotWrapper = document.getElementById("timeSlotWrapper");
    const timeSlotSelect = document.getElementById("time_slot");

    const timeSlots = {
        AM: ["9am–1pm", "10am–2pm", "11am–3pm"],
        PM: ["4pm-8pm", "5pm–9pm", "6pm–10pm"],
    };

    const dateStatuses = {};

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

    function applyStatusStyle(cell, status) {
        cell.classList.remove("Available", "Half", "Nearly", "Full", "Closed");
        switch (status) {
            case "Available":
                cell.classList.add("Available");
                break;
            case "Half":
                cell.classList.add("Half");
                break;
            case "Nearly":
                cell.classList.add("Nearly");
                break;
            case "Full":
                cell.classList.add("Full");
                break;
            case "Closed":
                cell.classList.add("Closed");
                break;
        }
    }

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

    // ✅ Agreement Modal Logic
    const agreementModal = document.getElementById("agreementModal");
    const agreementContent = document.getElementById("agreementContent");
    const agreeButton = document.getElementById("agreeButton");
    let hasScrolledToBottom = false;

    if (agreementModal && agreementContent && agreeButton) {
        const shouldShowAgreement = !window.guestAgreementAccepted;

        if (shouldShowAgreement) {
            setTimeout(() => {
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
                fetch(window.guestConsentAgreeUrl, {
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
                            agreementModal.classList.remove("show");
                            setTimeout(
                                () => (agreementModal.style.display = "none"),
                                300
                            );
                            form?.classList.remove("form-disabled");
                            document.body.style.overflow = "auto";
                        }
                    })
                    .catch((error) => {
                        console.error("Failed to save guest consent:", error);
                    });
            }
        });
    }

    // ✅ Calendar Rendering
    const calendar = document.getElementById("calendar");
    const monthYear = document.getElementById("month-year");
    const prevMonthBtn = document.getElementById("prevMonth");
    const nextMonthBtn = document.getElementById("nextMonth");

    let currentDate = new Date();

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
            calendar.appendChild(dayHeader);
        });

        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement("div");
            emptyCell.classList.add("calendar-day", "empty");
            calendar.appendChild(emptyCell);
        }

        for (let day = 1; day <= lastDate; day++) {
            const paddedMonth = String(month + 1).padStart(2, "0");
            const paddedDay = String(day).padStart(2, "0");
            const dateKey = `${year}-${paddedMonth}-${paddedDay}`;

            const cell = document.createElement("div");
            cell.classList.add("calendar-day");
            cell.textContent = day;
            cell.setAttribute("data-date", dateKey);

            if (dateStatuses[dateKey]) {
                applyStatusStyle(cell, dateStatuses[dateKey]);
            }

            calendar.appendChild(cell);
        }
    }

    function applyStatusStyle(cell, status) {
        cell.classList.remove("Available", "Half", "Nearly", "Full", "Closed");
        switch (status) {
            case "Available":
                cell.classList.add("Available");
                break;
            case "Half":
                cell.classList.add("Half");
                break;
            case "Nearly":
                cell.classList.add("Nearly");
                break;
            case "Full":
                cell.classList.add("Full");
                break;
            case "Closed":
                cell.classList.add("Closed");
                break;
        }
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
        // Use preloaded data from server (eliminates an extra round-trip)
        if (window.__calendarAvailability) {
            Object.entries(window.__calendarAvailability).forEach(([date, status]) => {
                dateStatuses[date] = status;
            });
            renderCalendar();
        } else {
            // Fallback: fetch from API
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
    }

    loadAvailabilityData();

    const dateUnavailableModal = document.getElementById(
        "dateUnavailableModal"
    );
    const closeUnavailableModal = document.getElementById(
        "closeUnavailableModal"
    );

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
