/**
 * Payment page functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const paymentForm = document.getElementById('paymentForm');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate email
            if (emailInput && !validateEmail(emailInput.value)) {
                e.preventDefault();
                showInputError(emailInput, 'Email tidak valid');
                isValid = false;
            }
            
            // Validate phone
            if (phoneInput && !validatePhone(phoneInput.value)) {
                e.preventDefault();
                showInputError(phoneInput, 'Nomor telepon tidak valid');
                isValid = false;
            }
            
            // Check if payment method is selected
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Silakan pilih metode pembayaran');
                isValid = false;
            }
            
            if (isValid) {
                // Disable submit button to prevent double submission
                const submitBtn = document.querySelector('.btn-pay');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Memproses...';
                }
            }
        });
    }
    
    // Payment option selection
    const paymentOptions = document.querySelectorAll('.payment-option');
    paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Uncheck all other options
            paymentOptions.forEach(opt => {
                const radio = opt.querySelector('input[type="radio"]');
                if (radio !== this.querySelector('input[type="radio"]')) {
                    radio.checked = false;
                }
            });
            
            // Check this option
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
    
    // Input validation functions
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function validatePhone(phone) {
        // Allow +62 or 0 prefix, then 8-15 digits
        const re = /^(\+62|62|0)[0-9]{8,15}$/;
        return re.test(phone);
    }
    
    function showInputError(input, message) {
        // Remove any existing error
        const existingError = input.parentNode.querySelector('.input-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error class to input
        input.classList.add('error');
        
        // Create error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'input-error';
        errorDiv.textContent = message;
        errorDiv.style.color = '#dc2626';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '5px';
        
        // Add error message after input
        input.parentNode.appendChild(errorDiv);
        
        // Focus input
        input.focus();
        
        // Remove error when input changes
        input.addEventListener('input', function() {
            this.classList.remove('error');
            const error = this.parentNode.querySelector('.input-error');
            if (error) {
                error.remove();
            }
        }, { once: true });
    }
});