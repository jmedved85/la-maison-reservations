/* Admin page JavaScript entry point */

import './styles/app_admin.css';
import './confirmation-modal.js';
import './login.js';
import './register.js';

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
const dateInput = document.querySelector('#filter_date');

// Date constraints
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
tomorrow.setHours(0, 0, 0, 0);

const maxDate = new Date();
maxDate.setDate(maxDate.getDate() + 30);

// Flatpick initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Flatpickr for date only
    let datePicker = null;
    if (dateInput) {
        datePicker = flatpickr(dateInput, {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d. m. Y.",
            defaultDate: null,
            minDate: tomorrow,
            maxDate: maxDate,
            placeholder: "Select Date",
            disable: [
                function(date) {
                    return date < tomorrow;
                }
            ],
            onReady: function(selectedDates, dateStr, instance) {
                instance.altInput.style.cursor = 'pointer';
                instance.altInput.placeholder = 'Select Date';
            },
        });
    }
});