/**
 * Main JavaScript
 * Common functionality for all pages
 */

document.addEventListener('DOMContentLoaded', function () {
    // User dropdown toggle
    const userDropdownBtn = document.getElementById('userDropdownBtn');
    const userDropdown = userDropdownBtn?.closest('.user-dropdown');

    if (userDropdownBtn && userDropdown) {
        userDropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }

    // Mobile menu toggle (if needed in future)
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            console.log('Mobile menu toggle');
        });
    }
});
