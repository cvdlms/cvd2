/**
 * Toast Notification System for CVD
 * Professional toast notifications thay thế alert()
 */

// Tạo container cho toasts nếu chưa có
function initToastContainer() {
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
}

/**
 * Hiển thị toast notification
 * @param {string} message - Nội dung thông báo
 * @param {string} type - Loại: success, error, warning, info
 * @param {number} duration - Thời gian hiển thị (ms), mặc định 3000
 */
function showToast(message, type = 'info', duration = 3000) {
    initToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const iconMap = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    const bgMap = {
        success: 'bg-success',
        error: 'bg-danger',
        warning: 'bg-warning',
        info: 'bg-info'
    };
    
    const icon = iconMap[type] || iconMap.info;
    const bgClass = bgMap[type] || bgMap.info;
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <span class="me-2">${icon}</span>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    const container = document.getElementById('toast-container');
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: duration
    });
    
    toast.show();
    
    // Xóa toast sau khi ẩn
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Các hàm tiện ích
 */
window.showSuccessToast = function(message, duration = 3000) {
    showToast(message, 'success', duration);
};

window.showErrorToast = function(message, duration = 4000) {
    showToast(message, 'error', duration);
};

window.showWarningToast = function(message, duration = 3500) {
    showToast(message, 'warning', duration);
};

window.showInfoToast = function(message, duration = 3000) {
    showToast(message, 'info', duration);
};

/**
 * Copy to clipboard with toast notification
 */
window.copyToClipboard = function(text, successMessage = 'Đã copy vào clipboard!') {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            showSuccessToast(successMessage);
        }).catch(() => {
            showErrorToast('Không thể copy. Vui lòng thử lại!');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showSuccessToast(successMessage);
        } catch (err) {
            showErrorToast('Không thể copy. Vui lòng thử lại!');
        }
        
        document.body.removeChild(textArea);
    }
};

// Initialize khi document ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initToastContainer);
} else {
    initToastContainer();
}
