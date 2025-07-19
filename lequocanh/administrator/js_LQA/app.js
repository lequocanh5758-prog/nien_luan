/**
 * Modern JavaScript Application Framework
 * Improvement: Better frontend architecture
 */

class App {
    constructor() {
        this.components = new Map();
        this.eventBus = new EventBus();
        this.api = new ApiClient();
        this.notifications = new NotificationManager();
        this.init();
    }
    
    init() {
        this.setupGlobalErrorHandler();
        this.setupAjaxDefaults();
        this.registerComponents();
        this.bindGlobalEvents();
        console.log('App initialized successfully');
    }
    
    setupGlobalErrorHandler() {
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
            this.notifications.error('Đã xảy ra lỗi hệ thống');
        });
        
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
            this.notifications.error('Đã xảy ra lỗi không mong muốn');
        });
    }
    
    setupAjaxDefaults() {
        // Setup CSRF token for all AJAX requests
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token.getAttribute('content')
                }
            });
        }
        
        // Global AJAX error handler
        $(document).ajaxError((event, xhr, settings, thrownError) => {
            if (xhr.status === 419) {
                this.notifications.error('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
                setTimeout(() => location.reload(), 2000);
            } else if (xhr.status >= 500) {
                this.notifications.error('Lỗi máy chủ. Vui lòng thử lại sau.');
            }
        });
    }
    
    registerComponents() {
        // Auto-register components
        document.querySelectorAll('[data-component]').forEach(element => {
            const componentName = element.dataset.component;
            const componentClass = window[componentName];
            
            if (componentClass) {
                const instance = new componentClass(element);
                this.components.set(element, instance);
            }
        });
    }
    
    bindGlobalEvents() {
        // Confirm dialogs
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-confirm')) {
                const message = e.target.dataset.confirm;
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Auto-submit forms on change
        document.addEventListener('change', (e) => {
            if (e.target.hasAttribute('data-auto-submit')) {
                e.target.closest('form').submit();
            }
        });
        
        // Loading states
        document.addEventListener('submit', (e) => {
            if (e.target.tagName === 'FORM') {
                const submitBtn = e.target.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
                }
            }
        });
    }
    
    component(name, element) {
        return this.components.get(element);
    }
    
    emit(event, data) {
        this.eventBus.emit(event, data);
    }
    
    on(event, callback) {
        this.eventBus.on(event, callback);
    }
}

class EventBus {
    constructor() {
        this.events = {};
    }
    
    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    }
    
    emit(event, data) {
        if (this.events[event]) {
            this.events[event].forEach(callback => callback(data));
        }
    }
    
    off(event, callback) {
        if (this.events[event]) {
            this.events[event] = this.events[event].filter(cb => cb !== callback);
        }
    }
}

class ApiClient {
    constructor() {
        this.baseUrl = '/lequocanh/api/v1';
        this.defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };
    }
    
    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            method,
            headers: { ...this.defaultHeaders, ...options.headers },
            ...options
        };
        
        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            config.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, config);
            const result = await response.json();
            
            if (!response.ok) {
                throw new Error(result.message || 'API request failed');
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
    
    get(endpoint, options = {}) {
        return this.request('GET', endpoint, null, options);
    }
    
    post(endpoint, data, options = {}) {
        return this.request('POST', endpoint, data, options);
    }
    
    put(endpoint, data, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }
    
    delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }
}

class NotificationManager {
    constructor() {
        this.container = this.createContainer();
    }
    
    createContainer() {
        let container = document.getElementById('notifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notifications';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }
    
    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getIcon(type)}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        this.container.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            this.remove(notification);
        }, duration);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.remove(notification);
        });
        
        // Animate in
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
    }
    
    remove(notification) {
        notification.classList.add('hide');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
    
    success(message, duration) {
        this.show(message, 'success', duration);
    }
    
    error(message, duration) {
        this.show(message, 'error', duration);
    }
    
    warning(message, duration) {
        this.show(message, 'warning', duration);
    }
    
    info(message, duration) {
        this.show(message, 'info', duration);
    }
    
    getIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

// Component Base Class
class Component {
    constructor(element) {
        this.element = element;
        this.init();
    }
    
    init() {
        // Override in subclasses
    }
    
    on(event, selector, callback) {
        this.element.addEventListener(event, (e) => {
            if (e.target.matches(selector)) {
                callback.call(e.target, e);
            }
        });
    }
    
    find(selector) {
        return this.element.querySelector(selector);
    }
    
    findAll(selector) {
        return this.element.querySelectorAll(selector);
    }
}

// Example Components
class DataTable extends Component {
    init() {
        this.setupSearch();
        this.setupPagination();
        this.setupSorting();
    }
    
    setupSearch() {
        const searchInput = this.find('[data-search]');
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.search(e.target.value);
                }, 300);
            });
        }
    }
    
    setupPagination() {
        this.on('click', '[data-page]', (e) => {
            e.preventDefault();
            const page = e.target.dataset.page;
            this.loadPage(page);
        });
    }
    
    setupSorting() {
        this.on('click', '[data-sort]', (e) => {
            const column = e.target.dataset.sort;
            const direction = e.target.classList.contains('asc') ? 'desc' : 'asc';
            this.sort(column, direction);
        });
    }
    
    search(query) {
        // Implement search logic
        console.log('Searching for:', query);
    }
    
    loadPage(page) {
        // Implement pagination logic
        console.log('Loading page:', page);
    }
    
    sort(column, direction) {
        // Implement sorting logic
        console.log('Sorting by:', column, direction);
    }
}

class FormValidator extends Component {
    init() {
        this.rules = this.parseRules();
        this.setupValidation();
    }
    
    parseRules() {
        const rules = {};
        this.findAll('[data-rules]').forEach(input => {
            const rulesStr = input.dataset.rules;
            rules[input.name] = rulesStr.split('|');
        });
        return rules;
    }
    
    setupValidation() {
        this.element.addEventListener('submit', (e) => {
            if (!this.validate()) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        this.findAll('input, select, textarea').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
        });
    }
    
    validate() {
        let isValid = true;
        
        Object.keys(this.rules).forEach(fieldName => {
            const field = this.find(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    validateField(field) {
        const rules = this.rules[field.name] || [];
        const value = field.value.trim();
        
        for (const rule of rules) {
            if (!this.applyRule(rule, value, field)) {
                this.showError(field, this.getErrorMessage(rule, field));
                return false;
            }
        }
        
        this.clearError(field);
        return true;
    }
    
    applyRule(rule, value, field) {
        switch (rule) {
            case 'required':
                return value !== '';
            case 'email':
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            case 'numeric':
                return /^\d+$/.test(value);
            case 'min:8':
                return value.length >= 8;
            default:
                return true;
        }
    }
    
    getErrorMessage(rule, field) {
        const messages = {
            required: 'Trường này là bắt buộc',
            email: 'Email không hợp lệ',
            numeric: 'Chỉ được nhập số',
            'min:8': 'Tối thiểu 8 ký tự'
        };
        return messages[rule] || 'Giá trị không hợp lệ';
    }
    
    showError(field, message) {
        this.clearError(field);
        
        const error = document.createElement('div');
        error.className = 'field-error';
        error.textContent = message;
        
        field.classList.add('error');
        field.parentNode.appendChild(error);
    }
    
    clearError(field) {
        field.classList.remove('error');
        const error = field.parentNode.querySelector('.field-error');
        if (error) {
            error.remove();
        }
    }
}

// Initialize app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});