// =============================================
// PROFESSIONAL DASHBOARD JAVASCRIPT
// =============================================

class DashboardManager {
    constructor() {
        this.charts = {};
        this.isLoading = false;
        this.lastUpdated = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.hideLoadingOverlay();
        this.updateLastUpdatedTime();
        this.loadAllData();
    }

    setupEventListeners() {
        // Filter change events
        document
            .getElementById("inquiry-filter")
            .addEventListener("change", (e) => {
                this.fetchInquiryData(e.target.value);
            });

        document
            .getElementById("reservation-filter")
            .addEventListener("change", (e) => {
                this.loadReservationChart(e.target.value);
            });

        document
            .getElementById("theme-filter")
            .addEventListener("change", (e) => {
                this.fetchThemeData(e.target.value);
            });

        document
            .getElementById("inquiry-type-chart-filter")
            .addEventListener("change", (e) => {
                this.loadInquiryTypeChart(e.target.value);
            });

        // Auto-refresh every 5 minutes
        setInterval(() => {
            this.refreshDashboard();
        }, 300000);
    }

    hideLoadingOverlay() {
        setTimeout(() => {
            const overlay = document.getElementById("loading-overlay");
            if (overlay) {
                overlay.classList.add("hidden");
                setTimeout(() => overlay.remove(), 300);
            }
        }, 1000);
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

    async loadAllData() {
        try {
            this.isLoading = true;

            // Load all dashboard components
            await Promise.all([
                this.fetchInquiryData("month"),
                this.loadReservationChart("month"),
                this.fetchThemeData("month"),
                this.loadInquiryTypeChart("month"),
                this.loadActivityLog(),
                this.updateMetricsOverview(),
            ]);

            this.updateLastUpdatedTime();
            this.isLoading = false;
        } catch (error) {
            console.error("Error loading dashboard data:", error);
            this.showNotification("Error loading dashboard data", "error");
        }
    }

    updateMetricsOverview(total, change) {
        const elTotal = document.getElementById("total-inquiries-overview");
        const elChange = document.querySelector("#inquiry-change span");

        if (elTotal) {
            elTotal.textContent = total || 0;
        }

        if (elChange) {
            const symbol = change > 0 ? "+" : "";
            elChange.textContent = `${symbol}${change}% vs last period`;
        }
    }

    async fetchInquiryData(timeRange) {
        console.log("Fetching inquiry data...");

        try {
            const response = await fetch(
                `/admin/inquiry-data?filter=${timeRange}`
            );
            const data = await response.json();
            console.log("Inquiry data response:", data);

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

            // ✅ Add these two lines to update the graph card stats
            document.getElementById(
                "inquiry-avg-daily"
            ).textContent = `${data.average_per_day} / day`;
            document.getElementById("inquiry-peak-day").textContent =
                data.peak_day;
        } catch (error) {
            console.error("Inquiry data fetch failed:", error);
        }
    }

    updateInquiryChart(data) {
        const ctx = document.getElementById("inquiryChart")?.getContext("2d");
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.inquiry) {
            this.charts.inquiry.destroy();
        }

        this.charts.inquiry = new Chart(ctx, {
            type: "line",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: "Inquiries",
                        data: data.data || [],
                        borderColor: "#4f46e5",
                        backgroundColor: "rgba(79, 70, 229, 0.1)",
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

    async loadReservationChart(timeRange) {
        try {
            const response = await fetch(
                `/admin/reservation-data?filter=${timeRange}`
            );
            const data = await response.json();

            console.log("Reservation data response:", data);

            if (data.error) {
                throw new Error(data.error);
            }

            // ✅ Update chart
            this.updateReservationChart({
                labels: data.labels,
                data: data.data,
            });

            // ✅ Update top card metric
            const totalEl = document.getElementById(
                "total-reservations-overview"
            );
            if (totalEl) totalEl.textContent = data.total || 0;

            // ✅ Update bottom chart stats
            const bestEl = document.getElementById("reservation-best-month");
            const growthEl = document.getElementById("reservation-growth");
            if (bestEl) bestEl.textContent = data.best_period;
            if (growthEl) growthEl.textContent = `${data.growth_rate}%`;
        } catch (error) {
            console.error("Reservation data fetch failed:", error);
            this.showNotification("Failed to load reservation data", "error");
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

        this.charts.reservation = new Chart(ctx, {
            type: "bar",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        label: "Reservations",
                        data: data.data || [],
                        backgroundColor: "#10b981",
                        borderColor: "#059669",
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

    async fetchThemeData(timeRange) {
        console.log("Fetching theme data...");

        try {
            const response = await fetch(
                `/admin/theme-data?filter=${timeRange}`
            );
            const data = await response.json();

            console.log("Theme data response:", data);

            // ✅ Update chart
            this.updateThemeChart({
                labels: data.labels,
                data: data.data,
            });

            const mostEl = document.getElementById("most-popular-theme");
            const totalEl = document.getElementById("total-themes");

            if (mostEl) mostEl.textContent = data.most_popular;
            if (totalEl) totalEl.textContent = data.total_themes;
        } catch (error) {
            console.error("Theme data fetch failed:", error);
            this.showNotification("Failed to load theme data", "error");
        }
    }

    updateThemeChart(data) {
        const ctx = document.getElementById("themeChart")?.getContext("2d");
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (this.charts.theme) {
            this.charts.theme.destroy();
        }

        this.charts.theme = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.data || [],
                        backgroundColor: [
                            "#f59e0b",
                            "#ef4444",
                            "#8b5cf6",
                            "#06b6d4",
                            "#84cc16",
                            "#f97316",
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

    async loadInquiryTypeChart(timeRange) {
        try {
            const response = await fetch(
                `/admin/event-type-data?filter=${timeRange}`
            );
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            // Draw chart
            this.updateInquiryTypeChart(data);

            // Fix the element IDs to match Blade
            const commonEl = document.getElementById("top-inquiry-category");
            const responseRateEl = document.getElementById(
                "inquiry-response-rate"
            );

            if (commonEl) commonEl.textContent = data.most_common;
            if (responseRateEl)
                responseRateEl.textContent = `${data.total_types}`;
        } catch (error) {
            console.error("Error loading inquiry type chart:", error);
            this.showNotification("Failed to load inquiry type data", "error");
        }
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

        this.charts.inquiryType = new Chart(ctx, {
            type: "pie",
            data: {
                labels: data.labels || [],
                datasets: [
                    {
                        data: data.data || [],
                        backgroundColor: [
                            "#3b82f6",
                            "#10b981",
                            "#f59e0b",
                            "#ef4444",
                            "#8b5cf6",
                            "#06b6d4",
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

    async loadActivityLog() {
        try {
            const response = await fetch("/admin/dashboard-activities");
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            this.updateActivityLog(data.activities || []);
        } catch (error) {
            console.error("Error loading activity log:", error);
            this.showNotification("Failed to load activity log", "error");
        }
    }

    updateActivityLog(activities) {
        const logContainer = document.getElementById("activity-log");
        if (!logContainer) return;

        if (activities.length === 0) {
            logContainer.innerHTML =
                '<div class="no-activities">No recent activities</div>';
            return;
        }

        const activityHtml = activities
            .map(
                (activity) => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class='bx ${this.getActivityIcon(activity.type)}'></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">${activity.description}</div>
                    <div class="activity-time">${this.formatTime(
                        activity.timestamp
                    )}</div>
                </div>
            </div>
        `
            )
            .join("");

        logContainer.innerHTML = activityHtml;
    }

    getActivityIcon(type) {
        const icons = {
            inquiry: "bx-message-detail",
            reservation: "bx-calendar-check",
            user: "bx-user",
            system: "bx-cog",
            default: "bx-info-circle",
        };
        return icons[type] || icons.default;
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) {
            // Less than 1 minute
            return "Just now";
        } else if (diff < 3600000) {
            // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes} minute${minutes > 1 ? "s" : ""} ago`;
        } else if (diff < 86400000) {
            // Less than 1 day
            const hours = Math.floor(diff / 3600000);
            return `${hours} hour${hours > 1 ? "s" : ""} ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    async refreshDashboard() {
        if (this.isLoading) return;

        try {
            this.showNotification("Refreshing dashboard...", "info");
            await this.loadAllData();
            this.showNotification("Dashboard updated successfully", "success");
        } catch (error) {
            console.error("Error refreshing dashboard:", error);
            this.showNotification("Failed to refresh dashboard", "error");
        }
    }

    // Export functionality
    exportData(type, format = "csv") {
        const exportButton = document.querySelector(`[data-export="${type}"]`);
        if (exportButton) {
            exportButton.classList.add("loading");
        }

        fetch(`export_data.php?type=${type}&format=${format}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Export failed");
                }
                return response.blob();
            })
            .then((blob) => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = `${type}_export_${
                    new Date().toISOString().split("T")[0]
                }.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                this.showNotification(
                    `${type} data exported successfully`,
                    "success"
                );
            })
            .catch((error) => {
                console.error("Export error:", error);
                this.showNotification("Export failed", "error");
            })
            .finally(() => {
                if (exportButton) {
                    exportButton.classList.remove("loading");
                }
            });
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

// Initialize dashboard when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
    window.dashboardManager = new DashboardManager();

    // Handle window resize for responsive charts
    window.addEventListener("resize", () => {
        window.dashboardManager.handleResize();
    });

    // Setup export buttons
    document.querySelectorAll("[data-export]").forEach((button) => {
        button.addEventListener("click", (e) => {
            const type = e.target.dataset.export;
            const format = e.target.dataset.format || "csv";
            window.dashboardManager.exportData(type, format);
        });
    });
});

// Cleanup on page unload
window.addEventListener("beforeunload", () => {
    if (window.dashboardManager) {
        window.dashboardManager.destroy();
    }
});
