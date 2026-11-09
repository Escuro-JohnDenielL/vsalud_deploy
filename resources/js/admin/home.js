import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener("DOMContentLoaded", function () {
    const packageModal = new bootstrap.Modal(document.getElementById("packageModal"));
    const addPackageModal = new bootstrap.Modal(document.getElementById("addPackageModal"));
    const editPackageModal = new bootstrap.Modal(document.getElementById("editPackageModal"));
    const successModal = new bootstrap.Modal(document.getElementById("successModal"));
    const confirmDeleteModal = new bootstrap.Modal(document.getElementById("confirmDeleteModal"));

    const packages = {};

    // Branded placeholder for packages with no photo, or when a photo fails to load.
    const PACKAGE_FALLBACK = "/images/package_placeholder.svg";

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
                    const venueNames = (Array.isArray(packageData.venues) ? packageData.venues : [])
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

    function openPackageModal(packageName) {
        const data = packages[packageName];
        if (!data) return;

        document.getElementById("modalTitle").textContent = data.name;
        document.getElementById("modalDescription").textContent = data.description || "No description available";
        const image = data.image_path || PACKAGE_FALLBACK;

        document.getElementById("modalMainImage").src = image;

        const inclusionsList = document.getElementById("modalInclusions");
        inclusionsList.innerHTML = "";

        const inclusions = Array.isArray(data.inclusions)
            ? data.inclusions.filter(value => typeof value === "string" && value.trim() !== "")
            : [];

        if (inclusions.length > 0) {
            inclusions.forEach(inclusion => {
                const li = document.createElement("li");
                li.textContent = inclusion;
                inclusionsList.appendChild(li);
            });
        } else {
            const li = document.createElement("li");
            li.textContent = "No inclusions specified";
            inclusionsList.appendChild(li);
        }

        packageModal.show();
    }

    function createPackageCard(data) {
        const wrapper = document.createElement("div");
        wrapper.className = "package-card";
        wrapper.dataset.package = data.name;

        const image = data.image_path || PACKAGE_FALLBACK;
        const description = data.description || "No description available";
        const truncatedDesc = description.length > 80 ? description.substring(0, 80) + "..." : description;
        const venueNames = (Array.isArray(data.venues) ? data.venues : [])
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

        card.querySelector(".view-package")?.addEventListener("click", () => {
            openPackageModal(packageName);
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

    // Build one inclusion input row (shared by the Add and Edit modals)
    function createInclusionRow(container, value = "") {
        if (!container) return null;

        const row = document.createElement("div");
        row.className = "vs-inclusion-row mb-2";
        row.innerHTML = `
            <input type="text" name="inclusions[]" class="form-control" placeholder="Enter inclusion">
            <button type="button" class="admin-btn admin-btn-danger admin-btn-sm remove-inclusion">Remove</button>
        `;
        row.querySelector('input[name="inclusions[]"]').value = value;
        container.appendChild(row);

        return row;
    }

    // Collect every inclusion input in a container so the values are actually submitted
    // (empty values are filtered out server-side).
    function appendInclusions(formData, containerSelector) {
        const inputs = document.querySelectorAll(`${containerSelector} input[name="inclusions[]"]`);

        if (inputs.length === 0) {
            // Send a blank value so the server can tell "cleared" from "not touched"
            formData.append("inclusions[]", "");
            return;
        }

        inputs.forEach(input => formData.append("inclusions[]", input.value));
    }

    // Handle dynamic inclusions for add package form
    const addInclusionBtn = document.getElementById('addInclusion');
    if (addInclusionBtn) {
        addInclusionBtn.addEventListener('click', function() {
            createInclusionRow(document.getElementById('inclusionsContainer'));
        });
    }

    // Handle dynamic inclusions for edit package form
    const editAddInclusionBtn = document.getElementById('editAddInclusion');
    if (editAddInclusionBtn) {
        editAddInclusionBtn.addEventListener('click', function() {
            createInclusionRow(document.getElementById('editInclusionsContainer'));
        });
    }

    // Handle remove inclusion for existing inputs (delegated)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-inclusion')) {
            e.target.closest('.vs-inclusion-row').remove();
        }
    });

    console.log("Admin Package Manager initialized.");
});