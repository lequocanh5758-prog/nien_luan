// Toast Notification System
(function() {
    if (window.Toast) return;
    
    var _ToastNotification = function() {
        this.container = null;
        this._init();
    };

    _ToastNotification.prototype._init = function() {
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    };

    _ToastNotification.prototype.show = function(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;
        
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        
        var icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-times-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        
        toast.innerHTML = '<span class="toast-icon">' + (icons[type] || icons.info) + '</span>' +
            '<span class="toast-message">' + message + '</span>' +
            '<span class="toast-close">&times;</span>';
        
        this.container.appendChild(toast);
        
        var self = this;
        setTimeout(function() { toast.classList.add('show'); }, 10);
        
        toast.querySelector('.toast-close').addEventListener('click', function() {
            self.remove(toast);
        });
        
        if (duration > 0) {
            setTimeout(function() { self.remove(toast); }, duration);
        }
    };

    _ToastNotification.prototype.remove = function(toast) {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 300);
    };

    _ToastNotification.prototype.success = function(message, duration) {
        this.show(message, 'success', duration || 3000);
    };

    _ToastNotification.prototype.error = function(message, duration) {
        this.show(message, 'error', duration || 5000);
    };

    _ToastNotification.prototype.warning = function(message, duration) {
        this.show(message, 'warning', duration || 4000);
    };

    _ToastNotification.prototype.info = function(message, duration) {
        this.show(message, 'info', duration || 3000);
    };

    window.Toast = new _ToastNotification();
})();
