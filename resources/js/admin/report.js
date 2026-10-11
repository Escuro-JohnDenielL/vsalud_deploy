// =============================================
// PROFESSIONAL DASHBOARD JAVASCRIPT
// =============================================

// Chart.js is imported from the bundled npm package (it is already a
// package.json dependency). Previously it was pulled from a CDN with a plain
// <script> tag, which could finish loading after the first dashboard render and
// leave every chart blank until the user pressed "Refresh Data".
import Chart from "chart.js/auto";

class DashboardManager {
    constructor() {
        this.charts = {};
        this.isLoading = false;
        this.lastUpdated = null;
        this.hasLoadedOnce = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.updateLastUpdatedTime();

        // The overlay is dismissed once the first load settles (see
        // loadAllData). Keep a failsafe so a hung request can never leave the
        // page stuck behind the spinner.
        setTimeout(() => this.hideLoadingOverlay(), 15000);

        this.loadAllData();
    }

    setupEventListeners() {
        // One page-level period selector drives the KPIs, trends and all charts.
        const periodSelect = document.getElementById("report-period");
        if (periodSelect) {
            periodSelect.addEventListener("change", (e) => {
                this.loadAllData(e.target.value);
            });
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            this.refreshDashboard();
        }, 300000);
    }

    hideLoadingOverlay() {
        const overlay = document.getElementById("loading-overlay");
        if (!overlay) return;

        overlay.classList.add("hidden");
        setTimeout(() => overlay.remove(), 300);
    }

    updateLastUpdatedTime() {
        const now = new Date();
        const timeString = now.toLocaleString("en-US", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
            hour12: true,
        });
        const element = document.getElementById("last-updated-time");
        if (element) {
            element.textContent = timeString;
        }
        this.lastUpdated = now;
    }

    showNotification(message, type = "info") {
        // Create notification element
        const notification = document.createElement("div");
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class='bx bx-${
                type === "success"
                    ? "check-circle"
                    : type === "error"
                    ? "error-circle"
                    : "info-circle"
            }'></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    currentPeriod() {
        const select = document.getElementById("report-period");
        return select ? select.value : "month";
    }

    async loadAllData(period = this.currentPeriod()) {
        // Never run two loads at once — a second one would fight the first over
        // the same chart instances.
        if (this.isLoading) return;

        this.isLoading = true;

        try {
            // One request for the KPIs and all four charts. Each endpoint is
            // cheap to query (a few ms) but expensive to bootstrap (~200ms of
            // framework + session setup), and the dev server handles requests
            // one at a time, so a single call is far faster than five.
            const response = await fetch(
                `/admin/report-data?period=${encodeURIComponent(period)}`
            );

            if (!response.ok) {
                throw new Error(
                    `Report data request failed (${response.status})`
                );
            }

            const payload = await response.json();

            // If the user picked another period while this was in flight, let
            // that newer request win instead of overwriting it.
            if (payload.period && payload.period !== this.currentPeriod()) {
                return;
            }

            this.renderInquiry(payload.inquiries || {});
            this.renderReservations(payload.reservations || {});
            this.renderThemes(payload.themes || {});
            this.renderEventTypes(payload.eventTypes || {});

            this.updateLastUpdatedTime();
        } catch (error) {
            console.error("Error loading dashboard data:", error);
            this.showNotification("Error loading dashboard data", "error");
        } finally {
            // Always clear the flag, even on failure, otherwise the Refresh
            // Data button would silently stop responding.
            this.isLoading = false;
            this.hasLoadedOnce = true;
            this.hideLoadingOverlay();
        }
    }

    updateMetricsOverview(total, change) {
        const elTotal = document.getElementById("total-inquiries-overview");
        const elChange = document.querySelector("#inquiry-change span");
        const changeBox = document.getElementById("inquiry-change");

        if (elTotal) {
            elTotal.textContent = total || 0;
        }

        if (elChange) {
            const value = Number(change) || 0;
            const symbol = value > 0 ? "+" : "";
            elChange.textContent = `${symbol}${value}% vs last period`;

            // A decline must not look like a green up arrow.
            if (changeBox) {
                changeBox.classList.toggle("positive", value >= 0);
                changeBox.classList.toggle("negative", value < 0);
                const icon = changeBox.querySelector("i");
                if (icon) {
                    icon.classList.toggle("bx-trending-up", value >= 0);
                    icon.classList.toggle("bx-trending-down", value < 0);
                }
            }
        }
    }

    renderInquiry(data) {
        // Draw the inquiry chart
        this.updateInquiryChart({
            labels: data.labels,
            data: data.data,
        });

        // Update inquiry summary (if used)
        this.updateInquirySummary({
            summary: {
                total: data.total,
                pending: 0, // Optional: add if available
                completed: 0, // Optional: add if available
            },
        });

        // Update metric card
        this.updateMetricsOverview(data.total, data.change_percentage);

        // Update the graph card stats
        const avgEl = document.getElementById("inquiry-avg-daily");
        const peakEl = document.getElementById("inquiry-peak-day");
        if (avgEl) avgEl.textContent = `${data.average_per_day} / day`;
        if (peakEl) peakEl.textContent = data.peak_day;
    }

    chartHasData(data) {
        return Array.isArray(data) && data.some((value) => Number(value) > 0);
    }

    showChartEmpty(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !canvas.parentElement) return;

        canvas.style.display = "none";
        let note = canvas.parentElement.querySelector(".vs-chart-empty");
        if (!note) {
            note = document.createElement("p");
            note.className = "vs-chart-empty";
            canvas.parentElement.appendChild(note);
        }
        note.textContent = message || "No data for this period.";
    }

    clearChartEmpty(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !canvas.parentElement) return;

        canvas.style.display = "";
        const note = canvas.parentElement.querySelector(".vs-chart-empty");
        if (note) note.remove();
    }

    updateInquiryChart(data) {
        const ctx = document.getElementById("inquiryChart")?.getContext("2d");
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.inquiry) {
            this.charts.inquiry.destroy();
        }

        if (!this.chartHasData(data.data)) {
            if (this.charts.inquiry) { this.charts.inquiry.destroy(); this.charts.inquiry = null; }
            this.showChartEmpty("inquiryChart", "No inquiries for this period.");
            return;
        }
        this.clearChartEmpty("inquiryChart");

        this.charts.inquiry = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: "Inquiries",
                        data: data.data || [],
                        borderColor: "#006b53",
                        backgroundColor: "rgba(0, 107, 83, 0.12)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: {
                            color: "rgba(0, 0, 0, 0.1)",
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    }

    updateInquirySummary(data) {
        const summaryElement = document.getElementById("inquiry-summary");
        if (summaryElement && data.summary) {
            summaryElement.innerHTML = `
                <div class="summary-item">
                    <span class="label">Total:</span>
                    <span class="value">${data.summary.total || 0}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Pending:</span>
                    <span class="value pending">${
                        data.summary.pending || 0
                    }</span>
                </div>
                <div class="summary-item">
                    <span class="label">Completed:</span>
                    <span class="value completed">${
                        data.summary.completed || 0
                    }</span>
                </div>
            `;
        }
    }

    renderReservations(data) {
        // Update chart
        this.updateReservationChart({
            labels: data.labels,
            data: data.data,
        });

        // Update top card metric
        const totalEl = document.getElementById(
            "total-reservations-overview"
        );
        if (totalEl) totalEl.textContent = data.total || 0;

        // Update bottom chart stats
        const bestEl = document.getElementById("reservation-best-month");
        const growthEl = document.getElementById("reservation-growth");
        if (bestEl) bestEl.textContent = data.best_period;
        if (growthEl) growthEl.textContent = `${data.growth_rate}%`;

        // "Best Month"/"Best Week" follows the selected period.
        const bestLabelEl = document.getElementById(
            "reservation-best-month-label"
        );
        if (bestLabelEl) {
            bestLabelEl.textContent =
                (data.best_period_label || "Best period") + ":";
        }

        // Reservations KPI trend (danger + down arrow when negative).
        const resChangeBox = document.getElementById("reservation-change");
        const resChangeText = document.querySelector(
            "#reservation-change span"
        );
        if (resChangeText) {
            const value = Number(data.growth_rate) || 0;
            resChangeText.textContent = `${
                value > 0 ? "+" : ""
            }${value}% vs previous period`;
            if (resChangeBox) {
                resChangeBox.classList.toggle("positive", value >= 0);
                resChangeBox.classList.toggle("negative", value < 0);
                const icon = resChangeBox.querySelector("i");
                if (icon) {
                    icon.classList.toggle("bx-trending-up", value >= 0);
                    icon.classList.toggle("bx-trending-down", value < 0);
                }
            }
        }
    }

    updateReservationChart(data) {
        const ctx = document
            .getElementById("reservation-chart")
            ?.getContext("2d");

        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.reservation) {
            this.charts.reservation.destroy();
        }

        if (!this.chartHasData(data.data)) {
            if (this.charts.reservation) { this.charts.reservation.destroy(); this.charts.reservation = null; }
            this.showChartEmpty("reservation-chart", "No reservations for this period.");
            return;
        }
        this.clearChartEmpty("reservation-chart");

        this.charts.reservation = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: "Reservations",
                        data: data.data || [],
                        backgroundColor: "#009b77",
                        borderColor: "#006b53",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: {
                            color: "rgba(0, 0, 0, 0.1)",
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    }

    renderThemes(data) {
        // Update chart
        this.updateThemeChart({
            labels: data.labels,
            data: data.data,
        });

        const mostEl = document.getElementById("most-popular-theme");
        const totalEl = document.getElementById("total-themes");

        if (mostEl) mostEl.textContent = data.most_popular;
        if (totalEl) totalEl.textContent = data.total_themes;
    }

    updateThemeChart(data) {
        const ctx = document.getElementById("themeChart")?.getContext("2d");
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.theme) {
            this.charts.theme.destroy();
        }

        if (!this.chartHasData(data.data)) {
            if (this.charts.theme) { this.charts.theme.destroy(); this.charts.theme = null; }
            this.showChartEmpty("themeChart", "No theme data for this period.");
            return;
        }
        if ((data.labels || []).length <= 1) {
            if (this.charts.theme) { this.charts.theme.destroy(); this.charts.theme = null; }
            this.showChartEmpty("themeChart", (data.labels[0] || "Only one theme") + ": " + (data.data[0] || 0));
            return;
        }
        this.clearChartEmpty("themeChart");

        this.charts.theme = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.data || [],
                        backgroundColor: [
                            "#006b53",
                            "#009b77",
                            "#7a4b00",
                            "#46514c",
                            "#707c75",
                            "#004c3a",
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                        },
                    },
                },
            },
        });
    }

    renderEventTypes(data) {
        // Draw chart
        this.updateInquiryTypeChart(data);

        // Fix the element IDs to match Blade
        const commonEl = document.getElementById("top-inquiry-category");
        const responseRateEl = document.getElementById(
            "inquiry-response-rate"
        );

        if (commonEl) commonEl.textContent = data.most_common;
        if (responseRateEl) responseRateEl.textContent = `${data.total_types}`;
    }

    updateInquiryTypeChart(data) {
        const ctx = document
            .getElementById("inquiry-type-chart")
            ?.getContext("2d");

        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.inquiryType) {
            this.charts.inquiryType.destroy();
        }

        if (!this.chartHasData(data.data)) {
            if (this.charts.inquiryType) { this.charts.inquiryType.destroy(); this.charts.inquiryType = null; }
            this.showChartEmpty("inquiry-type-chart", "No category data for this period.");
            return;
        }
        if ((data.labels || []).length <= 1) {
            if (this.charts.inquiryType) { this.charts.inquiryType.destroy(); this.charts.inquiryType = null; }
            this.showChartEmpty("inquiry-type-chart", (data.labels[0] || "Only one category") + ": " + (data.data[0] || 0));
            return;
        }
        this.clearChartEmpty("inquiry-type-chart");

        this.charts.inquiryType = new Chart(ctx, {
            type: "pie",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.data || [],
                        backgroundColor: [
                            "#006b53",
                            "#009b77",
                            "#7a4b00",
                            "#46514c",
                            "#707c75",
                            "#4f8067",
                        ],
                        borderWidth: 2,
                        borderColor: "#ffffff",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "right",
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                        },
                    },
                },
            },
        });
    }

    async refreshDashboard() {
        if (this.isLoading) {
            this.showNotification("Dashboard is still loading...", "info");
            return;
        }

        try {
            this.showNotification("Refreshing dashboard...", "info");
            await this.loadAllData();
            this.showNotification("Dashboard updated successfully", "success");
        } catch (error) {
            console.error("Error refreshing dashboard:", error);
            this.showNotification("Failed to refresh dashboard", "error");
        }
    }

    // Utility method to handle responsive chart resizing
    handleResize() {
        Object.values(this.charts).forEach((chart) => {
            if (chart && typeof chart.resize === "function") {
                chart.resize();
            }
        });
    }

    // Cleanup method
    destroy() {
        Object.values(this.charts).forEach((chart) => {
            if (chart && typeof chart.destroy === "function") {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}

// Initialize dashboard once the DOM is available. Module scripts are deferred,
// so DOMContentLoaded is normally still pending — but if the document has
// already finished parsing we bootstrap immediately instead of waiting for an
// event that will never fire again.
function initDashboard() {
    if (window.dashboardManager) return;

    window.dashboardManager = new DashboardManager();

    // Handle window resize for responsive charts
    window.addEventListener("resize", () => {
        window.dashboardManager.handleResize();
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDashboard);
} else {
    initDashboard();
}

// Cleanup on page unload
window.addEventListener("beforeunload", () => {
    if (window.dashboardManager) {
        window.dashboardManager.destroy();
    }
});
