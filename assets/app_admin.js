/* Admin page JavaScript entry point */

import './styles/app_admin.css';
import './confirmation-modal.js';
import './login.js';
import './register.js';

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
const dateInput = document.querySelector('#filter_date');

// Flatpick initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for date only
    if (dateInput) {
        flatpickr(dateInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d. m. Y.",
            defaultDate: null,
            placeholder: "Select Date",
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.style.cursor = 'pointer';
                instance.altInput.placeholder = 'Select Date';
            },
        });
    }
});