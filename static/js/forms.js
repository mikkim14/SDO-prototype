/**
 * Form Handling JavaScript
 */

class FormHandler {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        this.errors = {};
        this.setupListeners();
    }
    
    setupListeners() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Add real-time validation
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
        });
    }
    
    validateField(field) {
        const rules = field.getAttribute('data-rules');
        if (!rules) return true;
        
        const value = field.value.trim();
        const ruleset = rules.split('|');
        
        for (const rule of ruleset) {
            if (rule === 'required' && !value) {
                this.addFieldError(field, `${this.getLabelText(field)} is required`);
                return false;
            }
            
            if (rule === 'email' && value && !this.isValidEmail(value)) {
                this.addFieldError(field, 'Invalid email format');
                return false;
            }
            
            if (rule === 'date' && value && !this.isValidDate(value)) {
                this.addFieldError(field, 'Invalid date format');
                return false;
            }
            
            if (rule === 'numeric' && value && isNaN(value)) {
                this.addFieldError(field, `${this.getLabelText(field)} must be a number`);
                return false;
            }
            
            if (rule.startsWith('min:')) {
                const min = parseInt(rule.split(':')[1]);
                if (value && value.length < min) {
                    this.addFieldError(field, `Minimum ${min} characters required`);
                    return false;
                }
            }
        }
        
        this.removeFieldError(field);
        return true;
    }
    
    addFieldError(field, message) {
        this.removeFieldError(field);
        field.classList.add('border-red-500', 'bg-red-50');
        
        const error = document.createElement('span');
        error.className = 'error-message text-red-600 text-sm mt-1 block';
        error.textContent = message;
        field.parentElement.appendChild(error);
    }
    
    removeFieldError(field) {
        field.classList.remove('border-red-500', 'bg-red-50');
        const error = field.parentElement.querySelector('.error-message');
        if (error) error.remove();
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        // Validate all fields
        let isValid = true;
        const inputs = this.form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            Toast.warning('Please fix the errors in the form');
            return;
        }
        
        const formData = new FormData(this.form);
        const submitBtn = this.form.querySelector('[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
        
        try {
            const response = await fetch(this.form.action, {
                method: this.form.method,
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                Toast.success(data.message);
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (this.form.onSuccess) {
                    this.form.onSuccess(data);
                } else {
                    this.form.reset();
                }
            } else {
                Toast.error(data.message);
            }
        } catch (error) {
            Toast.error('An error occurred while submitting the form');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
    
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    isValidDate(date) {
        return !isNaN(new Date(date).getTime());
    }
    
    getLabelText(field) {
        const label = field.previousElementSibling;
        if (label && label.tagName === 'LABEL') {
            return label.textContent.replace(/\s*\*\s*$/, '');
        }
        return field.name;
    }
}

// Initialize forms
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form.auto-validate').forEach(form => {
        new FormHandler(form);
    });
});
