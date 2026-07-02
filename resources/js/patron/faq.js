
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger menu functionality
    const hamburger = document.querySelector('.hamburger');
    const navbarMenu = document.querySelector('.navbar ul');
    
    if (hamburger && navbarMenu) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navbarMenu.classList.toggle('active');
        });
        
        // Close menu when clicking on a link (for mobile)
        const navLinks = document.querySelectorAll('.navbar a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navbarMenu.classList.remove('active');
            });
        });
    }

    // FAQ functionality
    const faqButtons = document.querySelectorAll(".faq-question");
    faqButtons.forEach(button => {
        button.addEventListener("click", () => {
            const faqItem = button.parentElement;
            faqItem.classList.toggle("active");
        });
    });
});
