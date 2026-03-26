/* Public page JavaScript entry point */

import './styles/app.css';

// Tooltip initialization
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

document.addEventListener('DOMContentLoaded', function() {
    const reservationForm = document.querySelector('.reservation-form');

    if (!reservationForm) {
        return;
    }

    // Form elements
    const dateInput = document.querySelector('#reservation_form_reservationDate');
    const typeSelect = document.querySelector('#reservation_form_reservationType');
    const partySizeInput = document.querySelector('#reservation_form_partySize');
    const timeSlotInput = document.querySelector('#reservation_form_timeSlot');

    // Date constraints
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);

    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 30);

    // Initialize Flatpickr for date only
    flatpickr(dateInput, {
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
        onChange: function(selectedDates, dateStr, instance) {
            handleDateChange(dateStr);
        }
    });

    // Initially disable type selection until date is chosen
    if (typeSelect) {
        const privateDiningOption = typeSelect.querySelector('option[value="private_dining"]');
        if (privateDiningOption) {
            privateDiningOption.style.display = 'none';
        }
    }

    // Create a custom select element for time slots
    let timeSlotSelect = null;
    if (timeSlotInput) {
        // Hide the original input
        timeSlotInput.style.display = 'none';

        // Create select element
        timeSlotSelect = document.createElement('select');
        timeSlotSelect.className = 'form-select';
        timeSlotSelect.id = 'timeslot_select_custom';
        timeSlotSelect.innerHTML = '<option value="">Select date, type, and party size first</option>';
        timeSlotSelect.disabled = true;

        // Insert select after the hidden input
        timeSlotInput.parentNode.insertBefore(timeSlotSelect, timeSlotInput.nextSibling);

        // Sync select value to hidden input
        timeSlotSelect.addEventListener('change', function() {
            timeSlotInput.value = this.value;
        });
    }

    // Helper function to get missing fields message
    function getMissingFieldsMessage() {
        const missing = [];
        if (!dateInput?.value) missing.push('date');
        if (!typeSelect?.value) missing.push('reservation type');
        if (!partySizeInput?.value) missing.push('party size');

        if (missing.length === 0) return null;

        return `Please select ${missing.join(', ')} first`;
    }

    // Handle date change
    function handleDateChange(dateStr) {
        if (!dateStr) {
            return;
        }

        // Clear time slot when date changes
        if (timeSlotSelect) {
            timeSlotSelect.value = '';
            timeSlotInput.value = '';
        }

        // Check if Private Dining should be available
        checkPrivateDiningAvailability(dateStr);

        // Load available time slots
        loadAvailableTimeSlots();
    }

    // Check if Private Dining is available for the selected date
    function checkPrivateDiningAvailability(dateStr) {
        if (!typeSelect) return;

        const privateDiningOption = typeSelect.querySelector('option[value="private_dining"]');

        if (!privateDiningOption) return;

        fetch(`/api/reservations/check-private-dining?date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                if (data.available) {
                    // Show Private Dining option
                    privateDiningOption.style.display = '';
                    privateDiningOption.disabled = false;
                    privateDiningOption.textContent = 'Private Dining';
                } else {
                    // Hide Private Dining option
                    privateDiningOption.style.display = 'none';
                    privateDiningOption.disabled = true;

                    // If Private Dining is currently selected, deselect it
                    if (typeSelect.value === 'private_dining') {
                        typeSelect.value = '';
                        // Reload slots since type changed
                        loadAvailableTimeSlots();
                    }
                }
            })
            .catch(error => {
                console.error('Error checking private dining availability:', error);
            })
        ;
    }

    // Load available time slots based on date, type, and party size
    function loadAvailableTimeSlots() {
        const date = dateInput?.value;
        const type = typeSelect?.value;
        const partySize = partySizeInput?.value;

        // Check if all required fields are filled
        const missingMessage = getMissingFieldsMessage();
        if (missingMessage) {
            if (timeSlotSelect) {
                timeSlotSelect.innerHTML = `<option value="">${missingMessage}</option>`;
                timeSlotSelect.disabled = true;
            }
            return;
        }

        // Show loading state
        if (timeSlotSelect) {
            timeSlotSelect.disabled = true;
            timeSlotSelect.innerHTML = '<option value="">Loading available slots...</option>';
        }

        // Fetch available slots
        fetch(`/api/reservations/available-slots?date=${date}&type=${type}&partySize=${partySize}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    if (timeSlotSelect) {
                        timeSlotSelect.innerHTML = `<option value="">${data.error}</option>`;
                        timeSlotSelect.disabled = true;
                    }
                    return;
                }

                if (data.slots && data.slots.length > 0) {
                    // Populate select with available time slots
                    let options = '<option value="">Select time slot</option>';

                    data.slots.forEach(slot => {
                        options += `<option value="${slot.value}">${slot.label}</option>`;
                    });

                    if (timeSlotSelect) {
                        timeSlotSelect.innerHTML = options;
                        timeSlotSelect.disabled = false;
                    }
                } else {
                    // No slots available
                    const message = data.message || 'No available slots for the selected criteria';

                    if (timeSlotSelect) {
                        timeSlotSelect.innerHTML = `<option value="">${message}</option>`;
                        timeSlotSelect.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading time slots:', error);
                if (timeSlotSelect) {
                    timeSlotSelect.innerHTML = '<option value="">Error loading slots. Please try again.</option>';
                    timeSlotSelect.disabled = true;
                }
            })
        ;
    }

    // Event listeners
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Update party size constraints based on type
            updatePartySizeConstraints();

            // Clear time slot when type changes
            if (timeSlotSelect) {
                timeSlotSelect.value = '';
                timeSlotInput.value = '';
            }
            loadAvailableTimeSlots();
        });
    }

    // Update party size min/max based on reservation type
    function updatePartySizeConstraints() {
        if (!partySizeInput || !typeSelect) return;

        const type = typeSelect.value;

        if (type === 'private_dining') {
            partySizeInput.min = 6;
            partySizeInput.max = 12;
            partySizeInput.placeholder = '6-12 guests';
        } else if (type === 'regular') {
            partySizeInput.min = 1;
            partySizeInput.max = 10;
            partySizeInput.placeholder = '1-10 guests';
        } else {
            partySizeInput.min = 1;
            partySizeInput.max = 12;
            partySizeInput.placeholder = 'Number of guests';
        }
    }

    if (partySizeInput) {
        partySizeInput.addEventListener('input', function() {
            // Clear time slot when party size changes
            if (timeSlotSelect) {
                timeSlotSelect.value = '';
                timeSlotInput.value = '';
            }
            loadAvailableTimeSlots();
        });
    }

    // Form submission with spinner
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
});