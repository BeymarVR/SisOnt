// Funcionalidades de autenticación ONT
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del formulario
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    // Toggle de contraseña
    setupPasswordToggles();
    
    // Validación en tiempo real
    if (loginForm) {
        setupLoginValidation();
    }
    
    if (registerForm) {
        setupRegisterValidation();
    }
    
    // Auto-hide alerts
    setupAutoHideAlerts();
    
    console.log('ONT Auth - Scripts loaded successfully');
});

// Configurar toggles de contraseña
function setupPasswordToggles() {
    const toggles = document.querySelectorAll('.password-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
}

// Configurar validación del login
function setupLoginValidation() {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    
    // Validación en tiempo real del email
    emailInput.addEventListener('blur', function() {
        validateEmail(this);
    });
    
    emailInput.addEventListener('input', function() {
        clearError(this);
    });
    
    // Validación en tiempo real de la contraseña
    passwordInput.addEventListener('blur', function() {
        validatePassword(this);
    });
    
    passwordInput.addEventListener('input', function() {
        clearError(this);
    });
    
    // Validación al enviar el formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailValid = validateEmail(emailInput);
        const passwordValid = validatePassword(passwordInput);
        
        if (emailValid && passwordValid) {
            showLoading(submitBtn);
            // Simular delay para mostrar loading
            setTimeout(() => {
                form.submit();
            }, 500);
        }
    });
}

// Configurar validación del registro
function setupRegisterValidation() {
    const form = document.getElementById('registerForm');
    const nombreInput = document.getElementById('nombre');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const termsCheckbox = document.getElementById('accept_terms');
    const submitBtn = document.getElementById('submitBtn');
    
    // Validación del nombre
    nombreInput.addEventListener('blur', function() {
        validateName(this);
    });
    
    nombreInput.addEventListener('input', function() {
        clearError(this);
    });
    
    // Validación del email
    emailInput.addEventListener('blur', function() {
        validateEmail(this);
        // Aquí podrías agregar verificación AJAX de disponibilidad
        checkEmailAvailability(this);
    });
    
    emailInput.addEventListener('input', function() {
        clearError(this);
        clearSuccess(this);
    });
    
    // Validación de la contraseña con medidor de fortaleza
    passwordInput.addEventListener('input', function() {
        validatePasswordStrength(this);
        clearError(this);
        
        // Revalidar confirmación si ya tiene contenido
        if (confirmPasswordInput.value) {
            validatePasswordMatch(confirmPasswordInput, this);
        }
    });
    
    // Validación de confirmación de contraseña
    confirmPasswordInput.addEventListener('input', function() {
        validatePasswordMatch(this, passwordInput);
        clearError(this);
    });
    
    // Validación de términos
    termsCheckbox.addEventListener('change', function() {
        validateTerms(this);
    });
    
    // Validación al enviar el formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nameValid = validateName(nombreInput);
        const emailValid = validateEmail(emailInput);
        const passwordValid = validatePasswordStrength(passwordInput);
        const confirmValid = validatePasswordMatch(confirmPasswordInput, passwordInput);
        const termsValid = validateTerms(termsCheckbox);
        
        if (nameValid && emailValid && passwordValid && confirmValid && termsValid) {
            showLoading(submitBtn);
            // Simular delay para mostrar loading
            setTimeout(() => {
                form.submit();
            }, 500);
        }
    });
}

// Validaciones individuales
function validateName(input) {
    const value = input.value.trim();
    const errorElement = document.getElementById('nombreError');
    
    if (value.length < 2) {
        showError(input, errorElement, 'El nombre debe tener al menos 2 caracteres');
        return false;
    }
    
    hideError(input, errorElement);
    return true;
}

function validateEmail(input) {
    const value = input.value.trim();
    const errorElement = document.getElementById('emailError');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailRegex.test(value)) {
        showError(input, errorElement, 'Por favor ingresa un email válido');
        return false;
    }
    
    hideError(input, errorElement);
    return true;
}

function validatePassword(input) {
    const value = input.value;
    const errorElement = document.getElementById('passwordError');
    
    if (value.length < 6) {
        showError(input, errorElement, 'La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    hideError(input, errorElement);
    return true;
}

function validatePasswordStrength(input) {
    const value = input.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('passwordStrength');
    const errorElement = document.getElementById('passwordError');
    
    if (value.length === 0) {
        strengthBar.className = 'strength-bar';
        strengthBar.style.width = '0%';
        hideError(input, errorElement);
        if (strengthText) strengthText.style.display = 'none';
        return false;
    }
    
    if (value.length < 6) {
        showError(input, errorElement, 'La contraseña debe tener al menos 6 caracteres');
        return false;
    }
    
    // Calcular fortaleza
    let strength = 0;
    let strengthClass = '';
    let strengthMessage = '';
    
    if (value.length >= 6) strength++;
    if (value.match(/[a-z]/)) strength++;
    if (value.match(/[A-Z]/)) strength++;
    if (value.match(/[0-9]/)) strength++;
    if (value.match(/[^a-zA-Z0-9]/)) strength++;
    
    switch (strength) {
        case 1:
        case 2:
            strengthClass = 'strength-weak';
            strengthMessage = 'Contraseña débil';
            break;
        case 3:
            strengthClass = 'strength-fair';
            strengthMessage = 'Contraseña regular';
            break;
        case 4:
            strengthClass = 'strength-good';
            strengthMessage = 'Contraseña buena';
            break;
        case 5:
            strengthClass = 'strength-strong';
            strengthMessage = 'Contraseña fuerte';
            break;
    }
    
    if (strengthBar) {
        strengthBar.className = `strength-bar ${strengthClass}`;
    }
    
    if (strengthText) {
        strengthText.style.display = 'flex';
        strengthText.querySelector('span').textContent = strengthMessage;
        strengthText.className = strength >= 3 ? 'form-message success' : 'form-message error';
        
        const icon = strengthText.querySelector('i');
        if (strength >= 3) {
            icon.className = 'bi bi-check-circle';
        } else {
            icon.className = 'bi bi-exclamation-circle';
        }
    }
    
    hideError(input, errorElement);
    return strength >= 2; // Mínimo regular
}

function validatePasswordMatch(confirmInput, passwordInput) {
    const confirmValue = confirmInput.value;
    const passwordValue = passwordInput.value;
    const errorElement = document.getElementById('confirmPasswordError');
    const successElement = document.getElementById('confirmPasswordSuccess');
    
    if (confirmValue.length === 0) {
        hideError(confirmInput, errorElement);
        hideSuccess(confirmInput, successElement);
        return false;
    }
    
    if (confirmValue !== passwordValue) {
        showError(confirmInput, errorElement, 'Las contraseñas no coinciden');
        hideSuccess(confirmInput, successElement);
        return false;
    }
    
    hideError(confirmInput, errorElement);
    showSuccess(confirmInput, successElement, 'Las contraseñas coinciden');
    return true;
}

function validateTerms(checkbox) {
    const errorElement = document.getElementById('termsError');
    
    if (!checkbox.checked) {
        showError(checkbox, errorElement, 'Debes aceptar los términos y condiciones');
        return false;
    }
    
    hideError(checkbox, errorElement);
    return true;
}

// Verificar disponibilidad del email (simulado)
function checkEmailAvailability(input) {
    const value = input.value.trim();
    const successElement = document.getElementById('emailSuccess');
    
    if (!value || !validateEmail(input)) {
        return;
    }
    
    // Simular verificación AJAX
    input.classList.add('validating');
    
    setTimeout(() => {
        input.classList.remove('validating');
        
        // Simular que el email está disponible (en producción sería una llamada AJAX real)
        const isAvailable = !['admin@ont.bo', 'test@test.com'].includes(value.toLowerCase());
        
        if (isAvailable && successElement) {
            showSuccess(input, successElement, 'Email disponible');
        }
    }, 1000);
}

// Funciones de utilidad para mostrar/ocultar errores y éxitos
function showError(input, errorElement, message) {
    input.classList.add('error');
    input.classList.remove('success');
    
    if (errorElement) {
        errorElement.querySelector('span').textContent = message;
        errorElement.style.display = 'flex';
    }
}

function hideError(input, errorElement) {
    input.classList.remove('error');
    
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function showSuccess(input, successElement, message) {
    input.classList.add('success');
    input.classList.remove('error');
    
    if (successElement) {
        successElement.querySelector('span').textContent = message;
        successElement.style.display = 'flex';
    }
}

function hideSuccess(input, successElement) {
    input.classList.remove('success');
    
    if (successElement) {
        successElement.style.display = 'none';
    }
}

function clearError(input) {
    input.classList.remove('error');
    const errorElement = input.closest('.form-group').querySelector('.form-message.error');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function clearSuccess(input) {
    input.classList.remove('success');
    const successElement = input.closest('.form-group').querySelector('.form-message.success');
    if (successElement) {
        successElement.style.display = 'none';
    }
}

// Mostrar estado de carga
function showLoading(button) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    button.classList.add('btn-loading');
    button.disabled = true;
    
    if (loadingOverlay) {
        loadingOverlay.classList.add('show');
    }
}

// Auto-hide alerts
function setupAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        // Auto-hide después de 5 segundos
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
        
        // Permitir cerrar manualmente
        alert.addEventListener('click', function() {
            this.style.opacity = '0';
            this.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                this.remove();
            }, 300);
        });
    });
}

// Funciones de utilidad adicionales
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert ${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'bi-check-circle' : 
                 type === 'error' ? 'bi-exclamation-triangle' : 'bi-info-circle';
    
    notification.innerHTML = `
        <i class="bi ${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
}

// Animación CSS adicional
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
