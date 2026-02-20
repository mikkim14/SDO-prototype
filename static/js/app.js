/**
 * Main Application JavaScript
 */

// Toast notification system
class Toast {
    static show(message, type = 'info', duration = 3000) {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        
        const bgColor = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'warning': 'bg-yellow-500',
            'info': 'bg-blue-500'
        };
        
        toast.className = `toast ${bgColor[type] || bgColor['info']} text-white px-6 py-3 rounded-lg shadow-lg flex items-center justify-between`;
        toast.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => toast.remove(), duration);
    }
    
    static success(message, duration = 3000) {
        this.show(message, 'success', duration);
    }
    
    static error(message, duration = 5000) {
        this.show(message, 'error', duration);
    }
    
    static warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    }
    
    static info(message, duration = 3000) {
        this.show(message, 'info', duration);
    }
}

// Modal system
class Modal {
    static show(title, content, options = {}) {
        const container = document.getElementById('modals-container');
        const modal = document.createElement('div');
        
        const buttons = options.buttons || [
            { label: 'Close', type: 'secondary', onclick: () => Modal.close() }
        ];
        
        let buttonsHtml = '';
        buttons.forEach(btn => {
            const bgColor = btn.type === 'danger' ? 'bg-red-600' : (btn.type === 'secondary' ? 'bg-gray-400' : 'bg-blue-600');
            buttonsHtml += `
                <button class="${bgColor} hover:${bgColor.replace('600', '700')} text-white px-4 py-2 rounded transition-colors"
                        onclick="${btn.onclick || "Modal.close()"}">
                    ${btn.label}
                </button>
            `;
        });
        
        modal.id = 'modal-overlay';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800">${title}</h2>
                </div>
                <div class="px-6 py-4">
                    ${content}
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
                    ${buttonsHtml}
                </div>
            </div>
        `;
        
        container.appendChild(modal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) Modal.close();
        });
    }
    
    static close() {
        const modal = document.getElementById('modal-overlay');
        if (modal) modal.remove();
    }
    
    static confirm(title, message, onConfirm, onCancel = null) {
        this.show(title, `<p class="text-gray-700">${message}</p>`, {
            buttons: [
                { label: 'Confirm', type: 'primary', onclick: onConfirm },
                { label: 'Cancel', type: 'secondary', onclick: onCancel ? onCancel : "Modal.close()" }
            ]
        });
    }
}

// API helper
class API {
    static async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, finalOptions);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            Toast.error(error.message);
            throw error;
        }
    }
    
    static get(url) {
        return this.request(url, { method: 'GET' });
    }
    
    static post(url, data) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    static put(url, data) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    static delete(url) {
        return this.request(url, { method: 'DELETE' });
    }
}

// Utility functions
const Utils = {
    formatDate(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },
    
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    formatNumber(num, decimals = 2) {
        return Number(num).toFixed(decimals);
    },
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('GHG System initialized');
});
