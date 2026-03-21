/* Public page JavaScript entry point */

import './styles/app.css';

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

// Flatpick initialization
document.addEventListener('DOMContentLoaded', function() {
    // const tomorrow = new Date();
    // tomorrow.setDate(tomorrow.getDate() + 1);
    // tomorrow.setHours(0, 0, 0, 0);
    
    // const maxDate = new Date();
    // maxDate.setDate(maxDate.getDate() + 30);
    
    // flatpickr("#reservation_form_reservationDate", {
    //     dateFormat: "Y-m-d",
    //     altInput: true,
    //     altFormat: "d. m. Y",
    //     defaultDate: null,
    //     minDate: tomorrow,
    //     maxDate: maxDate,
    //     disable: [
    //         function(date) {
    //             // Disable all dates before tomorrow
    //             return date < tomorrow;
    //         }
    //     ],
    //     onReady: function(selectedDates, dateStr, instance) {
    //         // Set cursor pointer on the alternate input
    //         instance.altInput.style.cursor = 'pointer';
    //     }
    // });

    // flatpickr("#reservation_form_timeSlot", {
    //     enableTime: true,
    //     noCalendar: true,
    //     dateFormat: "H:i",
    //     time_24hr: true,
    //     defaultDate: null
    // });

    // Reservation form submission with spinner
    const reservationForm = document.querySelector('.reservation-form');
    
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            const buttonText = submitButton.querySelector('span:not(.spinner-border):not(.sending-text)');
            const spinner = submitButton.querySelector('.spinner-border');
            const sendingText = submitButton.querySelector('.sending-text');
            
            // Show spinner and disable button
            if (spinner && sendingText && buttonText) {
                buttonText.classList.add('d-none');
                spinner.classList.remove('d-none');
                sendingText.classList.remove('d-none');
                submitButton.disabled = true;
            }
        });
    }
});