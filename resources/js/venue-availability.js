// "Venues on <date>" panel shared by the patron and admin Make Reservation
// pages.
//
// It reports each venue as Available or Occupied for the selected date (and
// period) and keeps the Venue select in sync: occupied venues are disabled in
// both places. The endpoint is public and only returns venue names, periods and
// an occupied flag, so nothing here is trusted for correctness; the server
// rechecks the slot when the form is submitted.

const LABEL_OCCUPIED = "Occupied";
const LABEL_AVAILABLE = "Available";
const LABEL_LOADING = "Loading venues...";
const LABEL_ERROR = "Could not load venue availability. Please try again.";
const LABEL_FULLY_BOOKED = "Fully booked for this date";
const LABEL_CLOSED = "This date is closed";
const LABEL_NO_VENUES = "No venues are configured yet.";

function normalise(value) {
    return (value || "").toString().trim().replace(/\s+/g, " ").toLowerCase();
}

function formatLongDate(dateKey) {
    const [year, month, day] = (dateKey || "").split("-").map(Number);
    if (!year || !month || !day) return dateKey;

    return new Intl.DateTimeFormat("en-US", {
        month: "long",
        day: "numeric",
        year: "numeric",
    }).format(new Date(Date.UTC(year, month - 1, day)));
}

/**
 * @param {object} options
 * @param {HTMLInputElement} options.dateInput   Date field (#field_date)
 * @param {HTMLSelectElement} [options.periodSelect] Period field (#field_period)
 * @param {HTMLSelectElement} [options.venueSelect]  Venue field (#field_venue)
 * @param {HTMLElement} options.panel            Panel container
 */
export function initVenueAvailability({ dateInput, periodSelect, venueSelect, panel }) {
    if (!dateInput || !panel) return;

    const endpoint = panel.dataset.endpoint;
    if (!endpoint) return;

    const titleEl = panel.querySelector("[data-vs-venue-title]");
    const statusEl = panel.querySelector("[data-vs-venue-status]");
    const messageEl = panel.querySelector("[data-vs-venue-message]");
    const listEl = panel.querySelector("[data-vs-venue-list]");
    const retryBtn = panel.querySelector("[data-vs-venue-retry]");

    let venues = [];
    let closed = false;
    let eventCount = 0;
    let dailyCap = 0;
    let requestId = 0;
    let timer = null;

    const currentPeriod = () => (periodSelect?.value || "").trim().toUpperCase();

    function setStatus(text, isError) {
        if (!statusEl) return;
        statusEl.textContent = text || "";
        statusEl.hidden = !text;
        statusEl.classList.toggle("vs-venue-panel__status--error", Boolean(isError));
    }

    function setMessage(text) {
        if (!messageEl) return;
        messageEl.textContent = text || "";
        messageEl.hidden = !text;
    }

    function setRetryVisible(visible) {
        if (retryBtn) retryBtn.hidden = !visible;
    }

    function reset() {
        venues = [];
        closed = false;
        eventCount = 0;
        dailyCap = 0;
        panel.hidden = true;
        setStatus("");
        setMessage("");
        setRetryVisible(false);
        if (listEl) listEl.innerHTML = "";
    }

    // A closed date blocks every venue; a venue with no determinable period is
    // reported as occupied for both, so it counts here too.
    function isOccupied(venue, period) {
        if (closed) return true;
        if (period) return Boolean(venue.occupied?.[period]);

        return Boolean(venue.occupied?.AM) && Boolean(venue.occupied?.PM);
    }

    function statusText(venue, period) {
        if (closed) return LABEL_OCCUPIED;

        if (period) {
            return isOccupied(venue, period) ? LABEL_OCCUPIED : LABEL_AVAILABLE;
        }

        const am = Boolean(venue.occupied?.AM);
        const pm = Boolean(venue.occupied?.PM);

        if (am && pm) return LABEL_OCCUPIED;
        if (!am && !pm) return LABEL_AVAILABLE;

        return `${am ? "AM" : "PM"} occupied, ${am ? "PM" : "AM"} available`;
    }

    function findOption(name) {
        if (!venueSelect) return null;

        return Array.from(venueSelect.options).find(
            (option) => normalise(option.value) === normalise(name)
        );
    }

    // Occupied venues must not be selectable in the dropdown either.
    function syncVenueSelect() {
        if (!venueSelect) return;

        const period = currentPeriod();
        let selectedIsOccupied = false;

        venues.forEach((venue) => {
            const option = findOption(venue.name);
            if (!option) return;

            const occupied = isOccupied(venue, period);
            option.disabled = occupied;

            if (occupied && normalise(venueSelect.value) === normalise(venue.name)) {
                selectedIsOccupied = true;
            }
        });

        if (selectedIsOccupied) {
            venueSelect.value = "";
            venueSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }
    }

    function render() {
        const period = currentPeriod();
        const date = (dateInput.value || "").trim();

        panel.hidden = false;

        if (titleEl) {
            titleEl.textContent = date ? `Venues on ${formatLongDate(date)}` : "Venues";
        }

        setRetryVisible(false);
        if (listEl) listEl.innerHTML = "";

        if (closed) {
            setMessage(LABEL_CLOSED);
        } else if (dailyCap > 0 && eventCount >= dailyCap) {
            setMessage(LABEL_FULLY_BOOKED);
        } else if (venues.length === 0) {
            setMessage(LABEL_NO_VENUES);
        } else if (venues.every((venue) => isOccupied(venue, period))) {
            setMessage(LABEL_FULLY_BOOKED);
        } else {
            setMessage("");
        }

        venues.forEach((venue) => {
            const occupied = isOccupied(venue, period);
            const item = document.createElement("li");
            item.className = "vs-venue-panel__item";

            const button = document.createElement("button");
            button.type = "button";
            button.className = "vs-venue-panel__venue";
            button.dataset.venue = venue.name;

            const nameEl = document.createElement("span");
            nameEl.className = "vs-venue-panel__name";
            nameEl.textContent = venue.name;

            const stateEl = document.createElement("span");
            stateEl.className = "vs-venue-panel__state";
            stateEl.textContent = statusText(venue, period);

            if (occupied) {
                item.classList.add("vs-venue-panel__item--occupied");
                // Disabled, so it cannot be picked with the mouse or the keyboard.
                button.disabled = true;
                button.setAttribute("aria-disabled", "true");
                stateEl.classList.add("vs-venue-panel__state--occupied");
            } else {
                const selected =
                    normalise(venueSelect?.value) === normalise(venue.name);
                button.setAttribute("aria-pressed", String(selected));
                if (selected) button.classList.add("is-selected");
                stateEl.classList.add("vs-venue-panel__state--available");
                button.addEventListener("click", () => selectVenue(venue.name));
            }

            button.append(nameEl, stateEl);
            item.appendChild(button);
            listEl?.appendChild(item);
        });
    }

    function selectVenue(name) {
        if (!venueSelect) return;

        const option = findOption(name);
        if (!option || option.disabled) return;

        venueSelect.value = option.value;
        // Programmatic assignment does not fire "change", so the page's own
        // handlers (the "Others" toggle) are told explicitly.
        venueSelect.dispatchEvent(new Event("change", { bubbles: true }));
        render();
    }

    async function load() {
        const date = (dateInput.value || "").trim();

        if (!date) {
            reset();
            return;
        }

        const id = ++requestId;
        panel.hidden = false;
        setMessage("");
        setRetryVisible(false);
        setStatus(LABEL_LOADING, false);

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set("date", date);

            const period = currentPeriod();
            if (period) url.searchParams.set("period", period);

            const response = await fetch(url.toString(), {
                headers: { Accept: "application/json" },
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            if (id !== requestId) return;

            venues = Array.isArray(data.venues) ? data.venues : [];
            closed = Boolean(data.closed);
            eventCount = Number(data.count) || 0;
            dailyCap = Number(data.daily_cap) || 0;

            setStatus("");
            syncVenueSelect();
            render();
        } catch (error) {
            if (id !== requestId) return;

            console.error("Failed to load venue availability:", error);
            venues = [];
            closed = false;
            eventCount = 0;
            dailyCap = 0;
            panel.hidden = false;
            if (listEl) listEl.innerHTML = "";
            setMessage("");
            setStatus(LABEL_ERROR, true);
            setRetryVisible(true);
        }
    }

    function schedule() {
        window.clearTimeout(timer);
        timer = window.setTimeout(load, 150);
    }

    dateInput.addEventListener("change", schedule);
    dateInput.addEventListener("input", schedule);
    periodSelect?.addEventListener("change", schedule);
    retryBtn?.addEventListener("click", load);

    load();
}

export default initVenueAvailability;
