const formatPrice = function (amount) {
    return `₱${Number(amount).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

export function initVenuePriceEstimate() {
    document.querySelectorAll("[data-venue-price-estimate]").forEach((widget) => {
        const form = widget.closest("form");
        const venueSelect = form?.querySelector("#field_venue");
        const periodSelect = form?.querySelector("#field_period");
        const rateSelect = widget.querySelector("#venue_rate_id");
        const optionGroup = widget.querySelector(".venue-price-estimate__option");
        const summary = widget.querySelector(".venue-price-estimate__summary");
        if (!form || !venueSelect || !rateSelect || !optionGroup || !summary) return;

        let ratesByVenue = {};
        try {
            ratesByVenue = JSON.parse(widget.dataset.rates || "{}");
        } catch (error) {
            console.error("Could not read venue pricing data.", error);
            summary.textContent = "Venue estimates are unavailable. Please contact Villa Salud.";
            return;
        }

        const initialRateId = widget.dataset.selectedRate || "";
        let firstUpdate = true;

        const update = function () {
            const venueName = venueSelect.value;
            const previousRateId = rateSelect.value;
            const rates = Array.isArray(ratesByVenue[venueName]) ? ratesByVenue[venueName] : [];
            const period = String(periodSelect?.value || "").toUpperCase();
            const hasPeriodRates = rates.some((rate) => rate.period === "AM" || rate.period === "PM");

            rateSelect.replaceChildren();
            optionGroup.hidden = true;
            rateSelect.disabled = true;
            rateSelect.required = false;

            if (!venueName || venueName === "Others") {
                summary.textContent = "Choose a venue to see its estimate.";
                firstUpdate = false;
                return;
            }

            if (rates.length === 0) {
                summary.textContent = "No online estimate is set for this venue. Villa Salud will confirm the amount.";
                firstUpdate = false;
                return;
            }

            if (hasPeriodRates && period !== "AM" && period !== "PM") {
                summary.textContent = "Choose an AM or PM time slot to see this venue’s estimate.";
                firstUpdate = false;
                return;
            }

            const availableRates = rates.filter((rate) => (
                rate.period === null || rate.period === "" || rate.period === period
            ));

            if (availableRates.length === 0) {
                summary.textContent = "No estimate is configured for this venue and time slot.";
                firstUpdate = false;
                return;
            }

            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = "Choose a pricing option";
            rateSelect.appendChild(placeholder);

            availableRates.forEach((rate) => {
                const option = document.createElement("option");
                option.value = String(rate.id);
                const periodLabel = rate.period ? ` — ${rate.period} slot` : "";
                option.textContent = `${rate.label}${periodLabel} · ${formatPrice(rate.price)}`;
                option.dataset.price = String(rate.price);
                rateSelect.appendChild(option);
            });

            optionGroup.hidden = false;
            rateSelect.disabled = false;
            rateSelect.required = true;

            const retainedId = firstUpdate ? initialRateId : previousRateId;
            if (retainedId && availableRates.some((rate) => String(rate.id) === retainedId)) {
                rateSelect.value = retainedId;
            } else if (availableRates.length === 1) {
                rateSelect.value = String(availableRates[0].id);
            }

            const selectedRate = availableRates.find((rate) => String(rate.id) === rateSelect.value);
            summary.textContent = selectedRate
                ? `Estimated venue rental: ${formatPrice(selectedRate.price)}`
                : "Choose a pricing option to see the estimate.";
            firstUpdate = false;
        };

        venueSelect.addEventListener("change", update);
        periodSelect?.addEventListener("change", update);
        rateSelect.addEventListener("change", update);
        update();
    });
}
