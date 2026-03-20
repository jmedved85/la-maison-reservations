/* Admin page JavaScript entry point */

import './styles/app_admin.css';
import './confirmation-modal.js';
import './login.js';
import './register.js';

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

// Flatpick initialization
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#date", {
        locale: "hr",
        dateFormat: "d. m. Y.",
        defaultDate: null
    });
});