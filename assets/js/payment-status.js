/**
 * Payment Status Page Functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto refresh countdown
    const autoRefreshNote = document.querySelector('.auto-refresh-note');
    if (autoRefreshNote) {
        let countdown = 30;
        
        const countdownSpan = document.createElement('span');
        countdownSpan.id = 'refresh-countdown';
        countdownSpan.textContent = countdown;
        countdownSpan.style.fontWeight = 'bold';
        
        // Update the note text
        autoRefreshNote.innerHTML = `
            <p>Halaman ini akan diperbarui secara otomatis dalam <span id="refresh-countdown">${countdown}</span> detik.</p>
        `;
        
        // Start countdown
        const countdownInterval = setInterval(function() {
            countdown--;
            
            const countdownElement = document.getElementById('refresh-countdown');
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }
    
    // Copy to clipboard functionality
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const textToCopy = this.getAttribute('data-copy');
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        showCopiedMessage(this);
                    })
                    .catch(err => {
                        console.error('Failed to copy text: ', err);
                        fallbackCopyText(textToCopy, this);
                    });
            } else {
                fallbackCopyText(textToCopy, this);
            }
        });
    });
    
    // Fallback copy method
    function fallbackCopyText(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopiedMessage(button);
        } catch (err) {
            console.error('Fallback copy failed: ', err);
            alert('Failed to copy text. Please try again.');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Show copied message
    function showCopiedMessage(button) {
        const originalText = button.textContent;
        button.textContent = 'Disalin!';
        button.classList.add('copied');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);
    }
});