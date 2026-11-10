import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener("DOMContentLoaded", function () {
    const packageModalElement = document.getElementById("packageModal");
    const packageRatesModalElement = document.getElementById("packageRatesModal");
    const packageModalHost = document.createElement("div");
    packageModalHost.className = "vs-admin-dashboard vs-layout--admin";
    packageModalHost.appendChild(packageModalElement);
    packageModalHost.appendChild(packageRatesModalElement);
    document.body.appendChild(packageModalHost);
    const packageModalClose = document.getElementById("modalPackageClose");
    const ratesOpenButton = document.getElementById("modalRatesOpen");
    const ratesCloseButton = document.getElementById("modalVenueRatesClose");
    const ratesDoneButton = document.getElementById("modalVenueRatesDone");
    let packageModalTrigger = null;
    let ratesReturnFocus = null;
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
    const addPackageModal = new bootstrap.Modal(document.getElementById("addPackageModal"));
    const editPackageModal = new bootstrap.Modal(document.getElementById("editPackageModal"));
    const successModal = new bootstrap.Modal(document.getElementById("successModal"));
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));

    function restoreModalFocus(modalElement, fallbackId) {
        let returnFocus = null;

        modalElement.addEventListener("show.bs.modal", event => {
            returnFocus = event.relatedTarget instanceof HTMLElement ? event.relatedTarget : document.activeElement;
        });

        modalElement.addEventListener("hidden.bs.modal", () => {
            if (document.querySelector(".modal.show")) {
                returnFocus = null;
                return;
            }

            const target = returnFocus?.isConnected && returnFocus.getClientRects().length
                ? returnFocus
                : document.getElementById(fallbackId);
            if (target && !target.hasAttribute("disabled")) target.focus();
            returnFocus = null;
        });
    }

    restoreModalFocus(document.getElementById("editPackageModal"), "addPackageBtn");
    restoreModalFocus(document.getElementById("addPackageModal"), "addPackageBtn");

    const packages = {};

    // Branded placeholder for packages with no photo, or when a photo fails to load.
    const PACKAGE_FALLBACK = "/images/package_placeholder.svg";
    const formatPrice = amount => `₱${Number(amount).toLocaleString("en-PH", {
        minimumFractionDigits: Number.isInteger(Number(amount)) ? 0 : 2,
        maximumFractionDigits: 2,
    })}`;
    const uniqueVenues = venues => {
        const seen = new Set();
        return (Array.isArray(venues) ? venues : []).filter(venue => {
            const key = venue?.id != null ? `id:${venue.id}` : `name:${String(venue?.name || "").trim().toLowerCase()}`;
            if (!venue?.name || seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    };
    const packageImageUrl = path => {
        if (!path) return PACKAGE_FALLBACK;
        if (/^(https?:)?\/\//i.test(path) || path.startsWith("/")) return path;
        return `/storage/${path.replace(/^storage\//, "")}`;
    };

    // Pending delete state for confirm modal
    let pendingDelete = null;

    document.getElementById('confirmDeleteYes').addEventListener('click', function() {
        if (!pendingDelete) return;
        const { card, packageName } = pendingDelete;
        pendingDelete = null;

        const packageId = packages[packageName].id;
        const resetLoading = window.vsSetButtonLoading(this, 'Deleting...');
        fetch(`/admin/packages/${packageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                'Accept': 'application/json',
            }
        })
        .then(res => {
            if (!res.ok) {
                return readErrorMessage(res, "Failed to delete package. Please try again.")
                    .then(msg => { throw new Error(msg); });
            }
            return res.json();
        })
        .then(() => {
            card.remove();
            delete packages[packageName];
            confirmDeleteModal.hide();
            showSuccessModal(`"${packageName}" has been deleted successfully!`);
        })
        .catch(err => {
            console.error("Delete failed:", err);
            showToast(err.message);
        })
        .finally(resetLoading);
    });

    // Load packages on page load
    loadPackages();

    function loadPackages() {
        fetch("/admin/packages")
            .then(res => {
                if (!res.ok) throw new Error('Failed to load packages');
                return res.json();
            })
            .then(data => {
                // Clear existing packages object
                Object.keys(packages).forEach(key => delete packages[key]);
                
                // Only clear the container if we have database packages
                if (data.length > 0) {
                    document.getElementById("packagesContainer").innerHTML = "";
                    
                    data.forEach(pkg => {
                        packages[pkg.name] = pkg;
                        const card = createPackageCard(pkg);
                        document.getElementById("packagesContainer").appendChild(card);
                    });
                } else {
                    // If no database packages, work with existing HTML cards
                    console.log("No database packages found, using existing HTML cards");
                    initializeExistingCards();
                }
                updatePackageDetails();
            })
            .catch(err => {
                console.error("Error loading packages:", err);
                // Don't clear existing cards if API fails
                initializeExistingCards();
                console.log("Using existing HTML cards due to API error");
            });
    }

    function initializeExistingCards() {
        // Initialize existing HTML cards with event listeners
        document.querySelectorAll(".package-card").forEach(card => {
            const packageName = card.dataset.package;
            if (packageName && !packages[packageName]) {
                // Create a basic package object for hardcoded cards
                packages[packageName] = {
                    id: packageName.toLowerCase().replace(/\s+/g, '_'),
                    name: packageName,
                    description: "This is a sample package description. Edit this package to add more details.",
                    image_path: card.querySelector('img').src,
                    inclusions: ["Professional coordination", "Event setup and decoration", "Photography coverage", "Catering service"]
                };
            }
            attachPackageEventListeners(card);
        });
    }

    function showSuccessModal(message) {
        document.getElementById("successMessage").textContent = message;
        successModal.show();
    }

    // Notifications are confirmation modals (resources/js/notify.js) so every
    // success/error message can be acknowledged on desktop and mobile.
    function showToast(message, type = "error") {
        window.vsNotify(message, type);
    }

    // Laravel replies to a 422 with { message, errors: { field: [msg] } }.
    // Prefer the specific field error, then `message`, then the caller's text.
    function pickErrorMessage(data, fallback) {
        if (data && data.errors) {
            const first = Object.values(data.errors)[0];
            if (Array.isArray(first) && first.length) return first[0];
            if (typeof first === "string") return first;
        }
        return (data && data.message) || fallback;
    }

    // Read a failed response without assuming it is JSON — a 419/500 may return
    // an HTML error page, which would otherwise blow up on res.json().
    function readErrorMessage(res, fallback) {
        return res.json()
            .then(data => pickErrorMessage(data, fallback))
            .catch(() => fallback);
    }

    function updatePackageDetails() {
        document.querySelectorAll(".package-card").forEach(card => {
            const packageName = card.dataset.package;
            const packageData = packages[packageName];
            if (packageData) {
                const detailsDiv = card.querySelector(".package-details");
                if (detailsDiv) {
                    const description = packageData.description || "No description available";
                    const truncatedDesc = description.length > 80 ? description.substring(0, 80) + "..." : description;
                    const venueNames = uniqueVenues(packageData.venues)
                        .map(v => v.name)
                        .filter(Boolean);
                    const venueLabel = venueNames.length
                        ? "Venue" + (venueNames.length > 1 ? "s" : "") + ": " + venueNames.join(", ")
                        : "No venue linked";
                    detailsDiv.innerHTML = `
                        <p class="package-description">${truncatedDesc}</p>
                        <p class="package-venue">${venueLabel}</p>
                        <p class="package-pricing-note">Venue pricing is managed on the Venues page.</p>
                    `;
                }
            }
        });
    }

    function openPackageRatesModal() {
        if (ratesOpenButton.hidden || packageRatesModalElement.style.display === "flex") return;
        ratesReturnFocus = document.activeElement;
        window.vsModalPrepareOpen(packageRatesModalElement);
        packageRatesModalElement.hidden = false;
        packageRatesModalElement.style.display = "flex";
        ratesCloseButton.focus();
    }

    function closePackageRatesModal(restoreFocus = true) {
        if (packageRatesModalElement.hidden || packageRatesModalElement.style.display === "none") return;
        window.vsModalCloseAfterFade(packageRatesModalElement, () => {
            packageRatesModalElement.style.display = "none";
            packageRatesModalElement.hidden = true;
            if (restoreFocus && ratesReturnFocus?.isConnected) ratesReturnFocus.focus();
            ratesReturnFocus = null;
        });
    }

    function openPackageViewModal(trigger) {
        packageModalTrigger = trigger;
        window.vsModalPrepareOpen(packageModalElement);
        window.vsModalPrepareOpen(packageRatesModalElement);
        packageRatesModalElement.style.display = "none";
        packageRatesModalElement.hidden = true;
        packageModalElement.style.display = "flex";
        document.body.style.overflow = "hidden";
        packageModalClose.focus();
    }

    function closePackageViewModal() {
        closePackageRatesModal(false);
        window.vsModalCloseAfterFade(packageModalElement, () => {
            packageModalElement.style.display = "none";
            document.body.style.overflow = "";
            if (packageModalTrigger?.isConnected) packageModalTrigger.focus();
            packageModalTrigger = null;
        });
    }

    function trapModalFocus(event, modalElement) {
        if (event.key !== "Tab") return;
        const items = Array.from(modalElement.querySelectorAll(FOCUSABLE)).filter(element => element.offsetParent !== null);
        if (!items.length) return;

        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    packageModalClose.addEventListener("click", closePackageViewModal);
    ratesOpenButton.addEventListener("click", openPackageRatesModal);
    ratesCloseButton.addEventListener("click", () => closePackageRatesModal());
    ratesDoneButton.addEventListener("click", () => closePackageRatesModal());
    packageModalElement.addEventListener("click", event => {
        if (event.target === packageModalElement) closePackageViewModal();
    });
    packageRatesModalElement.addEventListener("click", event => {
        if (event.target === packageRatesModalElement) closePackageRatesModal();
    });
    document.addEventListener("keydown", event => {
        if (packageRatesModalElement.style.display === "flex") {
            if (event.key === "Escape") {
                event.preventDefault();
                closePackageRatesModal();
            } else {
                trapModalFocus(event, packageRatesModalElement);
            }
            return;
        }

        if (packageModalElement.style.display !== "flex") return;
        if (event.key === "Escape") {
            event.preventDefault();
            closePackageViewModal();
            return;
        }
        trapModalFocus(event, packageModalElement);
    });

    function openPackageModal(packageName, trigger) {
        const data = packages[packageName];
        if (!data) return;

        packageModalTrigger = trigger;
        document.getElementById("modalTitle").textContent = data.name;
        document.getElementById("modalDescription").textContent = data.description || "No description available";
        const imageEl = document.getElementById("modalMainImage");
        imageEl.dataset.fallbackApplied = "false";
        imageEl.classList.remove("is-fallback");
        imageEl.src = packageImageUrl(data.image_path);
        imageEl.alt = `${data.name} package photo`;

        const venues = uniqueVenues(data.venues);
        const prices = venues.flatMap(venue => Array.isArray(venue.rates) ? venue.rates.map(rate => Number(rate.price)) : []).filter(Number.isFinite);
        const priceRange = document.getElementById("modalPriceRange");
        priceRange.textContent = prices.length
            ? (Math.min(...prices) === Math.max(...prices)
                ? formatPrice(Math.min(...prices))
                : `${formatPrice(Math.min(...prices))}–${formatPrice(Math.max(...prices))}`)
            : "Contact us for venue pricing";
        priceRange.classList.toggle("pkg-modal__pricing-range--contact", prices.length === 0);
        const hasVenueRates = venues.some(venue => Array.isArray(venue.rates) && venue.rates.length > 0);
        ratesOpenButton.hidden = !hasVenueRates;
        packageRatesModalElement.hidden = true;
        packageRatesModalElement.style.display = "none";
        ratesReturnFocus = null;
        const venueSelect = document.getElementById("modalVenueSelect");
        venueSelect.replaceChildren();
        const placeholder = document.createElement("option");
        placeholder.value = "";
        placeholder.textContent = venues.length ? "Select a venue" : "No venues linked";
        venueSelect.appendChild(placeholder);
        venues.forEach((venue, index) => {
            const option = document.createElement("option");
            option.value = String(index);
            option.textContent = venue.name;
            venueSelect.appendChild(option);
        });
        venueSelect.disabled = venues.length === 0;
        const venueRates = document.getElementById("modalVenueRates");
        venueRates.replaceChildren();
        const renderVenueRates = () => {
            venueRates.replaceChildren();
            const venue = venues[Number(venueSelect.value)];
            if (!venue) return;
            const rates = Array.isArray(venue.rates) ? venue.rates : [];
            (rates.length ? rates : [{ label: "Contact us for this venue's rates" }]).forEach(rate => {
                const item = document.createElement("li");
                item.textContent = rate.price == null
                    ? rate.label
                    : `${rate.label || "Venue rental"}${rate.period ? ` (${rate.period})` : ""}: ${formatPrice(rate.price)}`;
                venueRates.appendChild(item);
            });
        };
        venueSelect.onchange = renderVenueRates;
        if (venues.length === 1) {
            venueSelect.value = "0";
            renderVenueRates();
        }

        const inclusionsList = document.getElementById("modalInclusions");
        inclusionsList.innerHTML = "";
        const inclusionsSection = document.getElementById("modalInclusionsSection");

        const inclusions = Array.isArray(data.inclusions)
            ? data.inclusions.filter(value => typeof value === "string" && value.trim() !== "")
            : [];

        if (inclusions.length > 0) {
            inclusionsSection.hidden = false;
            inclusions.forEach(inclusion => {
                const li = document.createElement("li");
                const check = document.createElementNS("http://www.w3.org/2000/svg", "svg");
                check.setAttribute("viewBox", "0 0 16 16");
                check.setAttribute("aria-hidden", "true");
                check.setAttribute("focusable", "false");
                check.classList.add("pkg-modal__inclusion-check");
                const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                path.setAttribute("d", "m3 8 3.2 3.2L13 4.5");
                path.setAttribute("fill", "none");
                path.setAttribute("stroke", "currentColor");
                path.setAttribute("stroke-linecap", "round");
                path.setAttribute("stroke-linejoin", "round");
                path.setAttribute("stroke-width", "1.8");
                check.appendChild(path);

                const label = document.createElement("span");
                label.className = "pkg-modal__inclusion-text";
                label.textContent = inclusion;
                li.append(check, label);
                inclusionsList.appendChild(li);
            });
        } else {
            inclusionsSection.hidden = true;
        }

        openPackageViewModal(trigger);
    }

    function createPackageCard(data) {
        const wrapper = document.createElement("div");
        wrapper.className = "package-card";
        wrapper.dataset.package = data.name;

        const image = packageImageUrl(data.image_path);
        const description = data.description || "No description available";
        const truncatedDesc = description.length > 80 ? description.substring(0, 80) + "..." : description;
        const venueNames = uniqueVenues(data.venues)
            .map(v => v.name)
            .filter(Boolean);
        const venueLabel = venueNames.length
            ? "Venue" + (venueNames.length > 1 ? "s" : "") + ": " + venueNames.join(", ")
            : "No venue linked";

        wrapper.innerHTML = `
            <div class="package-card__media">
                <img src="${image}" alt="${data.name}" data-package-image class="${data.image_path ? '' : 'is-fallback'}">
            </div>
            <div class="package-content">
                <h3>${data.name}</h3>
                <div class="package-details">
                    <p class="package-description">${truncatedDesc}</p>
                    <p class="package-venue">${venueLabel}</p>
                    <p class="package-pricing-note">Venue pricing is managed on the Venues page.</p>
                </div>
                <div class="vs-admin-dashboard__card-actions">
                    <div class="vs-row-actions">
                        <button type="button" class="vs-row-actions__view view-package">View Package</button>
                        <div class="vs-overflow" data-overflow>
                            <button type="button" class="vs-overflow__toggle" data-overflow-toggle aria-haspopup="menu" aria-expanded="false" aria-label="Package actions">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
                            </button>
                            <div class="vs-overflow__menu" data-overflow-menu role="menu" hidden>
                                <button type="button" class="vs-overflow__item edit-btn">Edit</button>
                                <div class="vs-overflow__separator"></div>
                                <button type="button" class="vs-overflow__item vs-overflow__item--danger delete-btn">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        attachPackageEventListeners(wrapper);
        return wrapper;
    }

    function attachPackageEventListeners(card) {
        const packageName = card.dataset.package;

        card.querySelector(".view-package")?.addEventListener("click", event => {
            openPackageModal(packageName, event.currentTarget);
        });

        card.querySelector(".edit-btn")?.addEventListener("click", () => {
            const data = packages[packageName];
            if (!data) return;

            document.getElementById("editPackageId").value = data.id;
            document.getElementById("editPackageName").value = data.name;
            document.getElementById("editPackageDescription").value = data.description || "";
            const venueIds = (Array.isArray(data.venues) ? data.venues : []).map(v => String(v.id));
            document.querySelectorAll("#editPackageVenues .edit-package-venue").forEach(cb => {
                cb.checked = venueIds.includes(cb.value);
            });

            // Prefill the inclusions editor with this package's current inclusions
            const inclusionsContainer = document.getElementById("editInclusionsContainer");
            if (inclusionsContainer) {
                inclusionsContainer.innerHTML = "";
                const inclusions = Array.isArray(data.inclusions)
                    ? data.inclusions.filter(value => typeof value === "string" && value.trim() !== "")
                    : [];
                inclusions.forEach(value => createInclusionRow(inclusionsContainer, value));
            }

            // The edit modal is one shared form and a file input keeps the File it
            // was last given. Without this reset, saving a second package would
            // silently re-upload the previously selected photo onto it.
            resetEditImageInputs();

            applyImagePreview("currentMainImage", data.image_path);

            editPackageModal.show();
        });

        card.querySelector(".delete-btn")?.addEventListener("click", () => {
            pendingDelete = { card, packageName };
            document.getElementById('confirmDeleteTitle').textContent = 'Delete Package';
            document.getElementById('confirmDeleteMessage').textContent = `Are you sure you want to delete "${packageName}"?`;
            confirmDeleteModal.show();
        });
    }

    // Add Package Form Handler
    const addPackageForm = document.getElementById("addPackageForm");
    if (addPackageForm) {
        addPackageForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const resetLoading = window.vsSetButtonLoading(
                e.submitter || this.querySelector('[type="submit"]'),
                'Adding package...'
            );

            const formData = new FormData();
            formData.append("name", document.getElementById("newPackageName").value);
            formData.append("description", document.getElementById("newPackageDescription").value);

            document.querySelectorAll("#newPackageVenues .new-package-venue:checked").forEach(cb => {
                formData.append("venues[]", cb.value);
            });

            appendInclusions(formData, "#inclusionsContainer");
            
            const image = document.getElementById("newPackageImage");
            if (image && image.files[0]) {
                formData.append("image", image.files[0]);
            }

            fetch("/admin/packages", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    return readErrorMessage(res, "Failed to create package. Please try again.")
                        .then(msg => { throw new Error(msg); });
                }
                return res.json();
            })
            .then(pkg => {
                packages[pkg.name] = pkg;
                const card = createPackageCard(pkg);
                document.getElementById("packagesContainer").appendChild(card);
                addPackageModal.hide();
                this.reset();
                showSuccessModal(`"${pkg.name}" has been added successfully!`);
            })
            .catch(err => {
                console.error("Add package error:", err);
                showToast(err.message);
            })
            .finally(resetLoading);
        });
    }

    // Edit Package Form Handler
    const editPackageForm = document.getElementById("editPackageForm");
    if (editPackageForm) {
        editPackageForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const resetLoading = window.vsSetButtonLoading(
                e.submitter || this.querySelector('[type="submit"]'),
                'Saving changes...'
            );

            const id = document.getElementById("editPackageId").value;
            const formData = new FormData();
            
            formData.append("_method", "PUT");
            formData.append("name", document.getElementById("editPackageName").value);
            formData.append("description", document.getElementById("editPackageDescription").value);

            document.querySelectorAll("#editPackageVenues .edit-package-venue:checked").forEach(cb => {
                formData.append("venues[]", cb.value);
            });

            appendInclusions(formData, "#editInclusionsContainer");

            const image = document.getElementById("editPackageImage");
            if (image && image.files[0]) {
                formData.append("image", image.files[0]);
            }

            fetch(`/admin/packages/${id}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    return readErrorMessage(res, "Failed to update package. Please try again.")
                        .then(msg => { throw new Error(msg); });
                }
                return res.json();
            })
            .then(pkg => {
                packages[pkg.name] = pkg;
                editPackageModal.hide();
                loadPackages(); // Reload packages to reflect changes
                showSuccessModal(`"${pkg.name}" has been updated successfully!`);
            })
            .catch(err => {
                console.error("Edit error:", err);
                showToast(err.message);
            })
            .finally(resetLoading);
        });
    }

    // Swap any package photo that fails to load for the branded placeholder.
    // Scoped to package images so unrelated page images are left alone.
    document.addEventListener('error', function(e) {
        const img = e.target;
        if (!img || img.tagName !== 'IMG') return;
        const PACKAGE_IMAGE_IDS = ['modalMainImage', 'currentMainImage'];
        const isPackageImage = img.hasAttribute('data-package-image') || PACKAGE_IMAGE_IDS.indexOf(img.id) !== -1;
        if (!isPackageImage || img.dataset.fallbackApplied === 'true') return;
        img.dataset.fallbackApplied = 'true';
        if (img.getAttribute('src') === PACKAGE_FALLBACK) return;
        img.src = PACKAGE_FALLBACK;
        img.classList.add('is-fallback');
    }, true);

    // Clear the file input in the shared edit modal.
    function resetEditImageInputs() {
        ["editPackageImage"].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.value = "";
        });
    }

    // Show the stored photo when the package has one, hide the placeholder when it does not.
    function applyImagePreview(imgId, path) {
        const img = document.getElementById(imgId);
        if (!img) return;

        if (path) {
            img.src = path;
            img.style.display = "";
        } else {
            img.removeAttribute("src");
            img.style.display = "none";
        }
    }

    let activeInclusionReorder = null;
    const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");

    // Build one inclusion row (shared by the Add and Edit modals)
    function createInclusionRow(container, value = "", afterRow = null) {
        if (!container) return null;

        const row = document.createElement("li");
        row.className = "vs-inclusion-row";
        row.innerHTML = `
            <button type="button" class="vs-inclusion-handle" aria-label="Reorder inclusion" aria-pressed="false" aria-keyshortcuts="Space Enter ArrowUp ArrowDown Escape">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <circle cx="6" cy="5" r="1.5"/><circle cx="14" cy="5" r="1.5"/>
                    <circle cx="6" cy="12" r="1.5"/><circle cx="14" cy="12" r="1.5"/>
                    <circle cx="6" cy="19" r="1.5"/><circle cx="14" cy="19" r="1.5"/>
                </svg>
            </button>
            <textarea name="inclusions[]" class="form-control vs-inclusion-text" rows="1" placeholder="Enter inclusion"></textarea>
            <button type="button" class="vs-inclusion-icon-button vs-inclusion-icon-button--remove remove-inclusion" aria-label="Remove inclusion">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                    <path d="M4 7h16M10 11v6m4-6v6M5 7l1 14h12l1-14M9 7V4h6v3"/>
                </svg>
            </button>
        `;
        const textarea = row.querySelector('textarea[name="inclusions[]"]');
        textarea.value = value;
        if (afterRow && afterRow.parentElement === container) {
            afterRow.after(row);
        } else {
            container.appendChild(row);
        }
        resizeInclusionTextarea(textarea);
        updateInclusionEditor(container);

        return row;
    }

    function resizeInclusionTextarea(textarea) {
        textarea.style.height = "auto";
        textarea.style.height = `${textarea.scrollHeight}px`;
        textarea.style.overflowY = "hidden";
    }

    function resizeInclusionList(container) {
        container?.querySelectorAll(".vs-inclusion-text").forEach(resizeInclusionTextarea);
    }

    function getInclusionRows(container) {
        return Array.from(container.querySelectorAll(".vs-inclusion-row"));
    }

    function animateInclusionChange(container, change) {
        const rows = getInclusionRows(container);
        const previousTops = new Map(rows.map(row => [row, row.getBoundingClientRect().top]));
        change();

        if (reducedMotionQuery.matches) return;

        const movedRows = getInclusionRows(container).filter(row => previousTops.has(row));
        movedRows.forEach(row => {
            const delta = previousTops.get(row) - row.getBoundingClientRect().top;
            if (delta) {
                row.style.transition = "none";
                row.style.transform = `translateY(${delta}px)`;
            }
        });

        if (movedRows.some(row => row.style.transform)) {
            void container.offsetHeight;
            window.requestAnimationFrame(() => {
                movedRows.forEach(row => {
                    if (row.isConnected) {
                        row.style.transition = "transform 160ms ease";
                        row.style.transform = "";
                    }
                });
            });
        }
    }

    function getInclusionLiveRegion(container) {
        const editor = container.closest(".vs-package-inclusions-editor");
        let liveRegion = editor?.querySelector(".vs-inclusion-live");
        if (!liveRegion && editor) {
            liveRegion = document.createElement("div");
            liveRegion.className = "vs-inclusion-live visually-hidden";
            liveRegion.setAttribute("aria-live", "polite");
            liveRegion.setAttribute("aria-atomic", "true");
            editor.appendChild(liveRegion);
        }
        return liveRegion;
    }

    function announceInclusionMove(state) {
        const rows = getInclusionRows(state.container);
        const position = rows.indexOf(state.row) + 1;
        const liveRegion = getInclusionLiveRegion(state.container);
        if (!position || !liveRegion) return;

        liveRegion.textContent = "";
        window.requestAnimationFrame(() => {
            liveRegion.textContent = `Inclusion ${state.originalPosition} moved to position ${position} of ${rows.length}`;
        });
    }

    function getRowPosition(row) {
        return getInclusionRows(row.parentElement).indexOf(row) + 1;
    }

    function updateInclusionHandleState(state, pressed) {
        state.handle.setAttribute("aria-pressed", String(pressed));
        state.row.classList.toggle("is-keyboard-grabbed", pressed);
    }

    function restoreInclusionOrder(state) {
        state.placeholder?.remove();
        state.originalRows.forEach(row => state.container.appendChild(row));
    }

    function clearPointerDragStyles(row) {
        row.classList.remove("is-pointer-dragging");
        ["position", "top", "left", "width", "zIndex", "margin", "pointerEvents", "transition", "transform"].forEach(property => {
            row.style[property] = "";
        });
    }

    function stopInclusionReorder(cancel = false) {
        const state = activeInclusionReorder;
        if (!state) return;

        activeInclusionReorder = null;
        if (state.scrollFrame) window.cancelAnimationFrame(state.scrollFrame);

        if (cancel) {
            restoreInclusionOrder(state);
        } else if (state.mode === "pointer" && state.placeholder?.parentElement === state.container) {
            state.container.insertBefore(state.row, state.placeholder);
            state.placeholder.remove();
        }

        if (state.mode === "pointer") {
            clearPointerDragStyles(state.row);
            if (state.handle.hasPointerCapture?.(state.pointerId)) {
                state.handle.releasePointerCapture(state.pointerId);
            }
        } else {
            updateInclusionHandleState(state, false);
        }
        state.container.classList.remove("is-reordering");
        updateInclusionEditor(state.container);
        resizeInclusionList(state.container);
        state.handle.focus({ preventScroll: true });

        if (!cancel && getRowPosition(state.row) !== state.originalPosition) {
            announceInclusionMove(state);
        }
    }

    function movePointerPlaceholder(state, pointerY) {
        const rows = getInclusionRows(state.container).filter(row => row !== state.row);
        const target = rows.find(row => pointerY <= row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2);
        const insert = () => {
            if (target) {
                if (state.placeholder.nextElementSibling !== target) {
                    state.container.insertBefore(state.placeholder, target);
                }
            } else if (state.container.lastElementChild !== state.placeholder) {
                state.container.appendChild(state.placeholder);
            }
        };
        const needsMove = target
            ? state.placeholder.nextElementSibling !== target
            : state.container.lastElementChild !== state.placeholder;
        if (needsMove) {
            animateInclusionChange(state.container, insert);
        }
    }

    function scrollInclusionArea(state) {
        const candidates = [
            state.container,
            state.container.closest(".modal-body")
        ].filter((element, index, list) => element && list.indexOf(element) === index);

        for (const element of candidates) {
            if (element.scrollHeight <= element.clientHeight + 1) continue;
            const rect = element.getBoundingClientRect();
            if (state.pointerY < rect.top || state.pointerY > rect.bottom) continue;

            const edge = Math.min(56, rect.height / 4);
            let amount = 0;
            if (state.pointerY < rect.top + edge && element.scrollTop > 0) {
                amount = -Math.ceil(12 * (1 - (state.pointerY - rect.top) / edge));
            } else if (state.pointerY > rect.bottom - edge && element.scrollTop + element.clientHeight < element.scrollHeight - 1) {
                amount = Math.ceil(12 * (1 - (rect.bottom - state.pointerY) / edge));
            }
            if (amount) {
                element.scrollTop += amount;
                break;
            }
        }
    }

    function startPointerAutoScroll(state) {
        const scroll = () => {
            if (activeInclusionReorder !== state) return;
            scrollInclusionArea(state);
            movePointerPlaceholder(state, state.pointerY);
            state.scrollFrame = window.requestAnimationFrame(scroll);
        };
        state.scrollFrame = window.requestAnimationFrame(scroll);
    }

    function startPointerInclusionReorder(handle, event) {
        const row = handle.closest(".vs-inclusion-row");
        const container = row?.closest(".vs-inclusion-list");
        if (!row || !container || activeInclusionReorder || event.button !== 0) return;

        event.preventDefault();
        handle.focus({ preventScroll: true });
        const rect = row.getBoundingClientRect();
        const originalRows = getInclusionRows(container);
        const placeholder = document.createElement("li");
        placeholder.className = "vs-inclusion-placeholder";
        placeholder.setAttribute("aria-hidden", "true");
        placeholder.style.height = `${rect.height}px`;
        container.insertBefore(placeholder, row);

        const state = {
            mode: "pointer",
            row,
            handle,
            container,
            placeholder,
            originalRows,
            originalPosition: originalRows.indexOf(row) + 1,
            pointerId: event.pointerId,
            pointerY: event.clientY,
            offsetY: event.clientY - rect.top
        };
        activeInclusionReorder = state;
        container.classList.add("is-reordering");
        row.classList.add("is-pointer-dragging");
        row.style.top = `${rect.top}px`;
        row.style.left = `${rect.left}px`;
        row.style.width = `${rect.width}px`;
        handle.setPointerCapture(event.pointerId);
        startPointerAutoScroll(state);
    }

    function startKeyboardInclusionReorder(handle) {
        const row = handle.closest(".vs-inclusion-row");
        const container = row?.closest(".vs-inclusion-list");
        if (!row || !container || activeInclusionReorder) return;

        const originalRows = getInclusionRows(container);
        const state = {
            mode: "keyboard",
            row,
            handle,
            container,
            originalRows,
            originalPosition: originalRows.indexOf(row) + 1
        };
        activeInclusionReorder = state;
        container.classList.add("is-reordering");
        updateInclusionHandleState(state, true);
    }

    function moveKeyboardInclusionRow(state, direction) {
        const rows = getInclusionRows(state.container);
        const index = rows.indexOf(state.row);
        const neighbor = rows[index + direction];
        if (!neighbor) return;

        animateInclusionChange(state.container, () => {
            if (direction < 0) {
                state.container.insertBefore(state.row, neighbor);
            } else {
                state.container.insertBefore(neighbor, state.row);
            }
        });
        updateInclusionEditor(state.container);
        state.handle.focus({ preventScroll: true });
        announceInclusionMove(state);
    }

    function updateInclusionEditor(container) {
        const rows = getInclusionRows(container);
        const editor = container.closest(".vs-package-inclusions-editor");
        const countLabel = editor?.querySelector(".form-label");

        if (countLabel) {
            countLabel.textContent = `Package inclusions (${rows.length})`;
        }

        rows.forEach((row, index) => {
            const position = index + 1;
            const handle = row.querySelector(".vs-inclusion-handle");
            const removeButton = row.querySelector(".remove-inclusion");
            const textarea = row.querySelector('textarea[name="inclusions[]"]');

            handle.setAttribute("aria-label", `Reorder inclusion ${position}`);
            handle.setAttribute("aria-pressed", String(activeInclusionReorder?.row === row && activeInclusionReorder.mode === "keyboard"));
            removeButton.setAttribute("aria-label", `Remove inclusion ${position}`);
            textarea.setAttribute("aria-label", `Package inclusion ${position}`);
        });
    }

    function focusInclusionRow(row) {
        const textarea = row?.querySelector('textarea[name="inclusions[]"]');
        if (textarea) {
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            return;
        }

        row?.closest(".vs-package-inclusions-editor")?.querySelector(".vs-inclusion-add")?.focus();
    }

    function focusInclusionHandle(row) {
        row?.querySelector(".vs-inclusion-handle")?.focus();
    }

    function removeInclusionRow(row, focusPrevious = false) {
        const container = row?.parentElement;
        if (!container) return;

        const previousRow = row.previousElementSibling;
        const nextRow = row.nextElementSibling;
        row.remove();
        updateInclusionEditor(container);

        const focusTarget = focusPrevious ? previousRow || nextRow : nextRow || previousRow;
        if (focusTarget) {
            focusInclusionHandle(focusTarget);
        } else {
            container.closest(".vs-package-inclusions-editor")?.querySelector(".vs-inclusion-add")?.focus();
        }
    }

    // Collect every inclusion value so the submitted order follows the visible list
    // (empty values are filtered out server-side).
    function appendInclusions(formData, containerSelector) {
        const inputs = document.querySelectorAll(`${containerSelector} textarea[name="inclusions[]"]`);

        if (inputs.length === 0) {
            // Send a blank value so the server can tell "cleared" from "not touched"
            formData.append("inclusions[]", "");
            return;
        }

        inputs.forEach(input => formData.append("inclusions[]", input.value));
    }

    const addInclusionsContainer = document.getElementById("inclusionsContainer");
    if (addInclusionsContainer) {
        createInclusionRow(addInclusionsContainer);
    }

    // Handle dynamic inclusions for add package form
    const addInclusionBtn = document.getElementById('addInclusion');
    if (addInclusionBtn) {
        addInclusionBtn.addEventListener('click', function() {
            const row = createInclusionRow(document.getElementById('inclusionsContainer'));
            row?.scrollIntoView({ block: "nearest" });
        });
    }

    // Handle dynamic inclusions for edit package form
    const editAddInclusionBtn = document.getElementById('editAddInclusion');
    if (editAddInclusionBtn) {
        editAddInclusionBtn.addEventListener('click', function() {
            const row = createInclusionRow(document.getElementById('editInclusionsContainer'));
            row?.scrollIntoView({ block: "nearest" });
        });
    }

    // Keep row controls scoped to their own inclusion list.
    document.addEventListener('click', function(e) {
        const button = e.target.closest("button");
        if (!button || !button.classList.contains("remove-inclusion")) return;

        const row = button.closest(".vs-inclusion-row");
        if (row?.closest(".vs-inclusion-list")) removeInclusionRow(row);
    });

    document.addEventListener("pointerdown", function(e) {
        const handle = e.target.closest(".vs-inclusion-handle");
        if (handle) startPointerInclusionReorder(handle, e);
    });

    document.addEventListener("pointermove", function(e) {
        const state = activeInclusionReorder;
        if (!state || state.mode !== "pointer" || e.pointerId !== state.pointerId) return;

        e.preventDefault();
        state.pointerY = e.clientY;
        state.row.style.top = `${e.clientY - state.offsetY}px`;
        movePointerPlaceholder(state, state.pointerY);
    }, { passive: false });

    document.addEventListener("pointerup", function(e) {
        const state = activeInclusionReorder;
        if (!state || state.mode !== "pointer" || e.pointerId !== state.pointerId) return;

        state.pointerY = e.clientY;
        movePointerPlaceholder(state, state.pointerY);
        stopInclusionReorder(false);
    });

    document.addEventListener("pointercancel", function(e) {
        const state = activeInclusionReorder;
        if (state?.mode === "pointer" && e.pointerId === state.pointerId) {
            stopInclusionReorder(true);
        }
    });

    document.addEventListener("keydown", function(e) {
        const state = activeInclusionReorder;
        if (e.key === "Escape" && state) {
            e.preventDefault();
            e.stopPropagation();
            stopInclusionReorder(true);
            return;
        }

        const handle = e.target.closest?.(".vs-inclusion-handle");
        if (!handle) return;

        if (state?.mode === "keyboard" && state.handle === handle) {
            if (e.key === " " || e.key === "Enter") {
                e.preventDefault();
                e.stopPropagation();
                stopInclusionReorder(false);
            } else if (e.key === "ArrowUp" || e.key === "ArrowDown") {
                e.preventDefault();
                e.stopPropagation();
                moveKeyboardInclusionRow(state, e.key === "ArrowUp" ? -1 : 1);
            }
            return;
        }

        if (!state && (e.key === " " || e.key === "Enter")) {
            e.preventDefault();
            e.stopPropagation();
            startKeyboardInclusionReorder(handle);
        }
    }, true);

    document.addEventListener("input", function(e) {
        if (e.target.matches(".vs-inclusion-text")) {
            resizeInclusionTextarea(e.target);
        }
    });

    document.addEventListener("keydown", function(e) {
        if (!e.target.matches(".vs-inclusion-text")) return;
        if (e.isComposing) return;

        if (e.key === "Enter") {
            e.preventDefault();
            if (!e.shiftKey) {
                const row = e.target.closest(".vs-inclusion-row");
                const newRow = createInclusionRow(row.parentElement, "", row);
                newRow?.scrollIntoView({ block: "nearest" });
                focusInclusionRow(newRow);
            }
            return;
        }

        if (e.key === "Backspace" && e.target.value === "") {
            e.preventDefault();
            removeInclusionRow(e.target.closest(".vs-inclusion-row"), true);
        }
    });

    document.addEventListener("paste", function(e) {
        if (!e.target.matches(".vs-inclusion-text")) return;

        const pastedText = e.clipboardData?.getData("text/plain");
        if (typeof pastedText !== "string" || !/[\r\n]/.test(pastedText)) return;

        e.preventDefault();
        const textarea = e.target;
        const cleanText = pastedText.replace(/[\r\n]+/g, " ");
        textarea.setRangeText(cleanText, textarea.selectionStart, textarea.selectionEnd, "end");
        resizeInclusionTextarea(textarea);
    });

    const inclusionLists = [
        document.getElementById("inclusionsContainer"),
        document.getElementById("editInclusionsContainer")
    ].filter(Boolean);
    inclusionLists.forEach(container => getInclusionLiveRegion(container));

    const inclusionResizeObserver = typeof ResizeObserver === "undefined"
        ? null
        : new ResizeObserver(entries => {
            entries.forEach(entry => resizeInclusionList(entry.target));
        });
    inclusionLists.forEach(container => inclusionResizeObserver?.observe(container));

    if (!inclusionResizeObserver) {
        window.addEventListener("resize", function() {
            inclusionLists.forEach(resizeInclusionList);
        });
    }

    ["addPackageModal", "editPackageModal"].forEach(modalId => {
        document.getElementById(modalId)?.addEventListener("shown.bs.modal", event => {
            const container = event.currentTarget.querySelector(".vs-inclusion-list");
            resizeInclusionList(container);
        });
    });

    console.log("Admin Package Manager initialized.");
});
