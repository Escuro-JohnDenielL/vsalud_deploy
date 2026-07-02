import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

document.addEventListener("DOMContentLoaded", function () {
    const packageModal = new bootstrap.Modal(document.getElementById("packageModal"));
    const addPackageModal = new bootstrap.Modal(document.getElementById("addPackageModal"));
    const editPackageModal = new bootstrap.Modal(document.getElementById("editPackageModal"));
    const successModal = new bootstrap.Modal(document.getElementById("successModal"));

    const packages = {};

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
                    price: 0,
                    image_path: card.querySelector('img').src,
                    inclusions: ["Professional coordination", "Event setup and decoration", "Photography coverage", "Catering service"]
                };
            }
            attachPackageEventListeners(card);
        });
    }

    function formatPrice(price) {
        return `₱${parseFloat(price).toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
    }

    function showSuccessModal(message) {
        document.getElementById("successMessage").textContent = message;
        successModal.show();
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
                    detailsDiv.innerHTML = `
                        <p class="package-description">${truncatedDesc}</p>
                        <p class="package-price">${formatPrice(packageData.price)}</p>
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
        document.getElementById("modalPrice").textContent = formatPrice(data.price);

        const images = [
            data.image_path || "/images/default_package.jpg",
            data.image_2_path || data.image_path || "/images/default_package.jpg",
            data.image_3_path || data.image_path || "/images/default_package.jpg"
        ];
        
        document.getElementById("modalMainImage").src = images[0];
        document.getElementById("modalImg1").src = images[0];
        document.getElementById("modalImg2").src = images[1];
        document.getElementById("modalImg3").src = images[2];

        const inclusionsList = document.getElementById("modalInclusions");
        inclusionsList.innerHTML = "";
        if (data.inclusions && Array.isArray(data.inclusions)) {
            data.inclusions.forEach(inclusion => {
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

        const image = data.image_path || "/images/default_package.jpg";
        const description = data.description || "No description available";
        const truncatedDesc = description.length > 80 ? description.substring(0, 80) + "..." : description;

        wrapper.innerHTML = `
            <img src="${image}" alt="${data.name}" onerror="this.src='/images/default_package.jpg'">
            <div class="package-content">
                <h3>${data.name}</h3>
                <div class="package-details">
                    <p class="package-description">${truncatedDesc}</p>
                    <p class="package-price">${formatPrice(data.price)}</p>
                </div>
                <div class="buttons-row">
                    <button class="btn btn-success btn-sm view-package">View Package</button>
                    <button class="btn btn-warning btn-sm text-white edit-btn">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn">Delete</button>
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
            document.getElementById("editPackagePrice").value = data.price;
            
            const currentImage = document.getElementById("currentMainImage");
            if (currentImage) {
                currentImage.src = data.image_path || "/images/default_package.jpg";
            }

            editPackageModal.show();
        });

        card.querySelector(".delete-btn")?.addEventListener("click", () => {
            if (!confirm(`Are you sure you want to delete "${packageName}"?`)) return;

            const packageId = packages[packageName].id;
            fetch(`/admin/packages/${packageId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    'Accept': 'application/json',
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Delete failed');
                return res.json();
            })
            .then(() => {
                card.remove();
                delete packages[packageName];
                showSuccessModal(`"${packageName}" has been deleted successfully!`);
            })
            .catch(err => {
                console.error("Delete failed:", err);
                alert("Failed to delete package. Please try again.");
            });
        });
    }

    // Add Package Form Handler
    const addPackageForm = document.getElementById("addPackageForm");
    if (addPackageForm) {
        addPackageForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append("name", document.getElementById("newPackageName").value);
            formData.append("description", document.getElementById("newPackageDescription").value);
            formData.append("price", document.getElementById("newPackagePrice").value);
            
            const image = document.getElementById("newPackageImage");
            if (image && image.files[0]) {
                console.log(image.files[0]);
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
                    return res.json().then(data => {
                        throw new Error(data.message || 'Failed to create package');
                    });
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
                alert("Failed to add package: " + err.message);
            });
        });
    }

    // Edit Package Form Handler
    const editPackageForm = document.getElementById("editPackageForm");
    if (editPackageForm) {
        editPackageForm.addEventListener("submit", function (e) {
            e.preventDefault();

            const id = document.getElementById("editPackageId").value;
            const formData = new FormData();
            
            formData.append("_method", "PUT");
            formData.append("name", document.getElementById("editPackageName").value);
            formData.append("description", document.getElementById("editPackageDescription").value);
            formData.append("price", document.getElementById("editPackagePrice").value);

            const image = document.getElementById("editPackageImage");
            if (image && image.files[0]) {
                formData.append("image", image.files[0]);
            }

            const image2 = document.getElementById("editPackageImage2");
            if (image2 && image2.files[0]) {
                formData.append("image2", image2.files[0]);
            }

            const image3 = document.getElementById("editPackageImage3");
            if (image3 && image3.files[0]) {
                formData.append("image3", image3.files[0]);
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
                    return res.json().then(data => {
                        throw new Error(data.message || 'Failed to update package');
                    });
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
                alert("Failed to update package: " + err.message);
            });
        });
    }

    // Setup thumbnail handlers for package modal
    setupThumbnailHandlers();

    function setupThumbnailHandlers() {
        const img1 = document.getElementById("modalImg1");
        const img2 = document.getElementById("modalImg2");
        const img3 = document.getElementById("modalImg3");
        const mainImg = document.getElementById("modalMainImage");

        if (img1 && mainImg) {
            img1.addEventListener("click", function () {
                mainImg.src = this.src;
            });
        }
        if (img2 && mainImg) {
            img2.addEventListener("click", function () {
                mainImg.src = this.src;
            });
        }
        if (img3 && mainImg) {
            img3.addEventListener("click", function () {
                mainImg.src = this.src;
            });
        }
    }

    // Global error handler for images
    document.addEventListener('error', function(e) {
        if (e.target.tagName === 'IMG') {
            e.target.src = '/images/default_package.jpg';
        }
    }, true);

    // Handle dynamic inclusions for add package form
    const addInclusionBtn = document.getElementById('addInclusion');
    if (addInclusionBtn) {
        addInclusionBtn.addEventListener('click', function() {
            const container = document.getElementById('inclusionsContainer');
            const newInclusion = document.createElement('div');
            newInclusion.className = 'input-group mb-2';
            newInclusion.innerHTML = `
                <input type="text" name="inclusions[]" class="form-control" placeholder="Enter inclusion">
                <button type="button" class="btn btn-outline-danger remove-inclusion">Remove</button>
            `;
            container.appendChild(newInclusion);
            
            // Add event listener to remove button
            newInclusion.querySelector('.remove-inclusion').addEventListener('click', function() {
                newInclusion.remove();
            });
        });
    }

    // Handle remove inclusion for existing inputs
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-inclusion')) {
            e.target.closest('.input-group').remove();
        }
    });

    console.log("Admin Package Manager initialized.");
});