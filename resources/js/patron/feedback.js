document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu functionality
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.navbar-menu.mobile');
    
    if (hamburger && mobileMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!hamburger.contains(event.target) && !mobileMenu.contains(event.target)) {
                hamburger.classList.remove('active');
                mobileMenu.classList.remove('active');
            }
        });

        // Close mobile menu when clicking on a link
        const mobileLinks = mobileMenu.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                mobileMenu.classList.remove('active');
            });
        });
    }

    // Form validation enhancement
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            clearError(this);
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        
        // Clear previous errors
        clearError(field);
        
        // Basic validation
        if (field.hasAttribute('required') && value === '') {
            showError(field, `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required.`);
            return false;
        }
        
        // Email validation
        if (field.type === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showError(field, 'Please enter a valid email address.');
                return false;
            }
        }
        
        return true;
    }
    
    function showError(field, message) {
        let errorElement = field.parentNode.querySelector('.error');
        if (!errorElement) {
            errorElement = document.createElement('span');
            errorElement.className = 'error';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        field.style.borderColor = '#dc3545';
    }
    
    function clearError(field) {
        const errorElement = field.parentNode.querySelector('.error');
        if (errorElement && !errorElement.textContent.includes('The ')) {
            errorElement.textContent = '';
        }
        field.style.borderColor = '#ccc';
    }
    
    // Form submission with loading state
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            // Validate all fields before submission
            let isValid = true;
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            submitButton.textContent = 'Sending...';
            submitButton.disabled = true;
            
            // Re-enable button after 3 seconds (in case of slow response)
            setTimeout(() => {
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            }, 3000);
        });
    }
    
    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-resize textarea
    const textarea = document.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });
    }
});