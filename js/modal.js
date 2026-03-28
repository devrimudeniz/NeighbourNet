/**
 * Custom Modal System for Kalkan Social
 * Replaces native confirm() and alert() with beautiful, site-themed modals
 */

const KalkanModal = {
    /**
     * Show a confirmation dialog
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {function} onConfirm - Callback when user confirms
     * @param {function} onCancel - Callback when user cancels (optional)
     */
    showConfirm(title, message, onConfirm, onCancel) {
        const modal = this._createModal('confirm', title, message);

        const confirmBtn = modal.querySelector('[data-action="confirm"]');
        const cancelBtn = modal.querySelector('[data-action="cancel"]');

        confirmBtn.onclick = () => {
            this._closeModal(modal);
            if (onConfirm) onConfirm();
        };

        cancelBtn.onclick = () => {
            this._closeModal(modal);
            if (onCancel) onCancel();
        };

        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this._closeModal(modal);
                if (onCancel) onCancel();
            }
        };

        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
    },

    /**
     * Show an alert dialog
     * @param {string} title - Modal title
     * @param {string} message - Modal message
     * @param {function} onClose - Callback when user closes (optional)
     */
    showAlert(title, message, onClose) {
        const modal = this._createModal('alert', title, message);

        const okBtn = modal.querySelector('[data-action="ok"]');

        okBtn.onclick = () => {
            this._closeModal(modal);
            if (onClose) onClose();
        };

        // Close on backdrop click
        modal.onclick = (e) => {
            if (e.target === modal) {
                this._closeModal(modal);
                if (onClose) onClose();
            }
        };

        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('active'), 10);
    },

    /**
     * Create modal HTML
     * @private
     */
    _createModal(type, title, message) {
        const isDark = document.documentElement.classList.contains('dark');

        const modal = document.createElement('div');
        modal.className = 'kalkan-modal';
        modal.innerHTML = `
            <div class="kalkan-modal-content">
                <div class="kalkan-modal-header">
                    <h3>${this._escapeHtml(title)}</h3>
                </div>
                <div class="kalkan-modal-body">
                    <p>${this._escapeHtml(message)}</p>
                </div>
                <div class="kalkan-modal-footer">
                    ${type === 'confirm' ? `
                        <button class="kalkan-btn kalkan-btn-secondary" data-action="cancel">İptal</button>
                        <button class="kalkan-btn kalkan-btn-danger" data-action="confirm">Onayla</button>
                    ` : `
                        <button class="kalkan-btn kalkan-btn-primary" data-action="ok">Tamam</button>
                    `}
                </div>
            </div>
        `;

        return modal;
    },

    /**
     * Close and remove modal
     * @private
     */
    _closeModal(modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    },

    /**
     * Escape HTML to prevent XSS
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Add CSS styles
const style = document.createElement('style');
style.textContent = `
    .kalkan-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
        padding: 1rem;
    }
    
    .kalkan-modal.active {
        opacity: 1;
    }
    
    .kalkan-modal-content {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 100%;
        overflow: hidden;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    
    .kalkan-modal.active .kalkan-modal-content {
        transform: scale(1);
    }
    
    .dark .kalkan-modal-content {
        background: #1e293b;
        color: white;
    }
    
    .kalkan-modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .dark .kalkan-modal-header {
        border-bottom-color: #334155;
    }
    
    .kalkan-modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }
    
    .dark .kalkan-modal-header h3 {
        color: white;
    }
    
    .kalkan-modal-body {
        padding: 1.5rem;
    }
    
    .kalkan-modal-body p {
        margin: 0;
        color: #64748b;
        line-height: 1.6;
    }
    
    .dark .kalkan-modal-body p {
        color: #cbd5e1;
    }
    
    .kalkan-modal-footer {
        padding: 1rem 1.5rem;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        background: #f8fafc;
    }
    
    .dark .kalkan-modal-footer {
        background: #0f172a;
    }
    
    .kalkan-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 80px;
    }
    
    .kalkan-btn:active {
        transform: scale(0.95);
    }
    
    .kalkan-btn-primary {
        background: #ec4899;
        color: white;
    }
    
    .kalkan-btn-primary:hover {
        background: #db2777;
    }
    
    .kalkan-btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .kalkan-btn-danger:hover {
        background: #dc2626;
    }
    
    .kalkan-btn-secondary {
        background: #e2e8f0;
        color: #475569;
    }
    
    .kalkan-btn-secondary:hover {
        background: #cbd5e1;
    }
    
    .dark .kalkan-btn-secondary {
        background: #334155;
        color: #cbd5e1;
    }
    
    .dark .kalkan-btn-secondary:hover {
        background: #475569;
    }
    
    @media (max-width: 640px) {
        .kalkan-modal {
            padding: 0.5rem;
        }
        
        .kalkan-modal-content {
            max-width: 100%;
        }
        
        .kalkan-modal-footer {
            flex-direction: column-reverse;
        }
        
        .kalkan-btn {
            width: 100%;
        }
    }
`;
document.head.appendChild(style);

// Make globally available
window.KalkanModal = KalkanModal;
