/**
 * KbadhaPay Payment JS Logic
 */

document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    const cardNumberInput = document.getElementById('cardNumber');
    const expiryInput = document.getElementById('expiryDate');
    const cvvInput = document.getElementById('cvv');
    const nameInput = document.getElementById('cardName');
    const card = document.querySelector('.payment-card');
    
    const paymentIdInput = document.getElementById('paymentId');
    const paymentTypeInput = document.getElementById('paymentType');

    // Handle Modal Data Population
    $('#paymentModal').on('show.bs.modal', function (event) {
        const button = $(event.relatedTarget);
        const id = button.data('id');
        const type = button.data('type');
        const amount = button.data('amount');
        
        if (id) {
            paymentIdInput.value = id;
            paymentTypeInput.value = type;
            console.log(`Paiement initialisé : ${type} #${id} (${amount} DT)`);
        }
    });
    
    // UI Display Elements
    const cardNumDisplay = document.getElementById('displayCardNumber');
    const cardNameDisplay = document.getElementById('displayCardName');
    const cardExpiryDisplay = document.getElementById('displayExpiry');
    const cardCvvDisplay = document.getElementById('displayCvv');
    const cardIcon = document.getElementById('cardTypeIcon');

    // --- Formatting & Input Handlers ---

    // Card Number Formatting
    cardNumberInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = '';
        for(let i = 0; i < value.length; i++) {
            if(i > 0 && i % 4 === 0) formattedValue += ' ';
            formattedValue += value[i];
        }
        e.target.value = formattedValue.substring(0, 19);
        cardNumDisplay.textContent = e.target.value || '**** **** **** ****';
        
        detectCardType(value);
        validateField(cardNumberInput, value.length >= 15 && isValidLuhn(value));
    });

    // Expiry Date Formatting (MM/AA)
    expiryInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/gi, '');
        if(value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value.substring(0, 5);
        cardExpiryDisplay.textContent = e.target.value || 'MM/AA';
        
        validateField(expiryInput, /^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(e.target.value));
    });

    // CVV
    cvvInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^0-9]/gi, '');
        e.target.value = value.substring(0, 4);
        cardCvvDisplay.textContent = '*'.repeat(value.length) || '***';
        
        const isAmex = cardNumberInput.value.startsWith('34') || cardNumberInput.value.startsWith('37');
        validateField(cvvInput, value.length === (isAmex ? 4 : 3));
    });

    // Name (Uppercase)
    nameInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase();
        cardNameDisplay.textContent = e.target.value || 'NOM DU TITULAIRE';
        validateField(nameInput, e.target.value.length > 2);
    });

    // --- Card Flip Logic ---
    cvvInput.addEventListener('focus', () => card.classList.add('flipped'));
    cvvInput.addEventListener('blur', () => card.classList.remove('flipped'));

    // --- Validation Utilities ---

    function validateField(input, isValid) {
        if(isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
        checkFormValidity();
    }

    function checkFormValidity() {
        const submitBtn = document.getElementById('btnSubmitPayment');
        const inputs = [cardNumberInput, expiryInput, cvvInput, nameInput];
        const allValid = inputs.every(input => input.classList.contains('is-valid'));
        submitBtn.disabled = !allValid;
    }

    function detectCardType(number) {
        let iconClass = 'far fa-credit-card';
        if(number.startsWith('4')) iconClass = 'fab fa-cc-visa text-white';
        else if(number.startsWith('5')) iconClass = 'fab fa-cc-mastercard text-white';
        else if(number.startsWith('34') || number.startsWith('37')) iconClass = 'fab fa-cc-amex text-white';
        
        cardIcon.className = 'card-type-icon ' + iconClass;
    }

    function isValidLuhn(number) {
        let sum = 0;
        for (let i = 0; i < number.length; i++) {
            let intVal = parseInt(number.substr(i, 1));
            if (i % 2 === number.length % 2) {
                intVal *= 2;
                if (intVal > 9) intVal -= 9;
            }
            sum += intVal;
        }
        return sum % 10 === 0;
    }

    // --- Form Submission ---

    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('btnSubmitPayment');
        const spinner = submitBtn.querySelector('.spinner-border');
        
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');

        const payload = {
            cardNumber: cardNumberInput.value,
            expiryDate: expiryInput.value,
            cvv: cvvInput.value,
            cardName: nameInput.value,
            paymentId: paymentIdInput.value,
            paymentType: paymentTypeInput.value
        };

        fetch('/payment/process', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === '3ds_required') {
                show3DSModal();
            } else if(data.status === 'success') {
                showPaymentSuccess();
            } else {
                showPaymentError(data.message || 'Erreur de paiement');
            }
        })
        .catch(err => {
            showPaymentError('Erreur de connexion au serveur');
        })
        .finally(() => {
            spinner.classList.add('d-none');
            if(!document.getElementById('modal3DS').classList.contains('show')) {
                submitBtn.disabled = false;
            }
        });
    });

    // --- 3D Secure Simulation ---

    let timerInterval;
    function show3DSModal() {
        $('#paymentModal').modal('hide');
        $('#modal3DS').modal('show');
        
        // Generate OTP
        const otp = Math.floor(100000 + Math.random() * 900000);
        console.log("DEBUG: Code OTP 3DS =", otp);
        document.getElementById('otpDisplay').textContent = otp; // Simulated SMS

        start3DSTimer(60);
    }

    function start3DSTimer(seconds) {
        let timeLeft = seconds;
        const timerText = document.getElementById('timerText');
        const progressCircle = document.getElementById('timerProgress');
        const resendBtn = document.getElementById('btnResendOTP');
        
        resendBtn.disabled = true;
        clearInterval(timerInterval);

        timerInterval = setInterval(() => {
            timeLeft--;
            timerText.textContent = timeLeft + 's';
            
            const offset = 283 - (timeLeft / 60) * 283;
            progressCircle.style.strokeDashoffset = offset;

            if(timeLeft <= 0) {
                clearInterval(timerInterval);
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    window.verifyOTP = function() {
        const input = document.getElementById('otpInput').value;
        const expected = document.getElementById('otpDisplay').textContent;
        const verifyBtn = document.getElementById('btnVerifyOTP');
        
        verifyBtn.disabled = true;
        
        if(input === expected) {
            $('#modal3DS').modal('hide');
            showPaymentSuccess();
        } else {
            const modalContent = document.querySelector('#modal3DS .modal-content');
            modalContent.classList.add('shake');
            setTimeout(() => modalContent.classList.remove('shake'), 500);
            alert('Code incorrect');
            verifyBtn.disabled = false;
        }
    };

    window.resendOTP = function() {
        const otp = Math.floor(100000 + Math.random() * 900000);
        console.log("DEBUG: Nouveau Code OTP 3DS =", otp);
        document.getElementById('otpDisplay').textContent = otp;
        start3DSTimer(60);
    };

    // --- Final UX States ---

    function showPaymentSuccess() {
        $('#paymentModal').modal('hide');
        $('#successModal').modal('show');
        
        setTimeout(() => {
            document.querySelector('.success-check').classList.add('show');
        }, 300);

        // Show Toast
        const toast = `
            <div class="toast show bg-success text-white" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
                <div class="toast-body">
                    <i class="fas fa-check-circle mr-2"></i>Paiement effectué avec succès !
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', toast);
        // Reload after close
        $('#successModal').on('hidden.bs.modal', function () {
            window.location.reload();
        });
    }

    function showPaymentError(msg) {
        const modalContent = document.querySelector('#paymentModal .modal-content');
        modalContent.classList.add('shake');
        setTimeout(() => modalContent.classList.remove('shake'), 500);
        
        alert(msg);
    }
});
