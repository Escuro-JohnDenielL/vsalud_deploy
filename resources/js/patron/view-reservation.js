document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("reservationForm");
    const modal = document.getElementById("reservationModal");
    const closeBtn = document.getElementById("closeReservationModal");
    const detailsContainer = document.getElementById("reservationDetails");

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const code = document.getElementById("reservation_code").value.trim();

        console.log(code);

        if (!code) return alert("Please enter your reservation code.");

        const csrf = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch("/fetch-reservation", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ reservation_code: code }),
            });

            const data = await response.json();

            if (!data.success) {
                alert(data.message || "Reservation not found.");
                return;
            }

            // Populate modal with reservation details
            detailsContainer.innerHTML = Object.entries(data.reservation)
                .map(
                    ([key, value]) => `<p><strong>${key}:</strong> ${value}</p>`
                )
                .join("");

            console.log(data);

            modal.style.display = "block";
        } catch (err) {
            alert("An error occurred while fetching the reservation.");
            console.error(err);
        }
    });

    closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) modal.style.display = "none";
    });
});
