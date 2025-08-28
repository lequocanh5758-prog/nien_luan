/**
 * Customer Notifications Widget
 * Widget thông báo cho khách hàng
 */

class CustomerNotifications {
  constructor() {
    // Tự động detect base URL
    const baseUrl =
      window.location.origin +
      window.location.pathname.replace(/\/[^\/]*$/, "");
    this.apiUrl =
      baseUrl +
      "/administrator/elements_LQA/mthongbao/getCustomerNotifications.php";
    this.updateInterval = 30000; // 30 giây
    this.init();
  }

  init() {
    this.createNotificationWidget();
    this.loadNotifications();
    this.startAutoUpdate();
    this.bindEvents();
  }

  createNotificationWidget() {
    // Tạo HTML cho widget thông báo
    const notificationHTML = `
            <div class="customer-notification-widget">
                <div class="notification-trigger" id="notificationTrigger">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </div>
                
                <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                    <div class="notification-header">
                        <h6><i class="fas fa-bell"></i> Thông báo</h6>
                        <button class="btn btn-sm btn-link mark-all-read" id="markAllRead">
                            Đánh dấu tất cả đã đọc
                        </button>
                    </div>
                    
                    <div class="notification-list" id="notificationList">
                        <div class="notification-loading">
                            <i class="fas fa-spinner fa-spin"></i> Đang tải...
                        </div>
                    </div>
                    
                    <div class="notification-footer">
                        <a href="index.php?req=lichsumuahang" class="btn btn-sm btn-primary">
                            Xem tất cả đơn hàng
                        </a>
                    </div>
                </div>
            </div>
        `;

    // Thêm CSS
    const style = document.createElement("style");
    style.textContent = `
            .customer-notification-widget {
                position: relative;
                display: inline-block;
            }
            
            .notification-trigger {
                position: relative;
                cursor: pointer;
                padding: 8px 12px;
                border-radius: 50%;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                transition: all 0.3s ease;
            }
            
            .notification-trigger:hover {
                background: #e9ecef;
                transform: scale(1.05);
            }
            
            .notification-badge {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #dc3545;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 11px;
                font-weight: bold;
                min-width: 18px;
                text-align: center;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            .notification-dropdown {
                position: absolute;
                top: 100%;
                right: 0;
                width: 350px;
                max-height: 400px;
                background: white;
                border: 1px solid #dee2e6;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                overflow: hidden;
            }
            
            .notification-header {
                padding: 12px 16px;
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .notification-header h6 {
                margin: 0;
                font-weight: 600;
            }
            
            .notification-list {
                max-height: 300px;
                overflow-y: auto;
            }
            
            .notification-item {
                padding: 12px 16px;
                border-bottom: 1px solid #f1f3f4;
                cursor: pointer;
                transition: background 0.2s ease;
            }
            
            .notification-item:hover {
                background: #f8f9fa;
            }
            
            .notification-item.unread {
                background: #e3f2fd;
                border-left: 3px solid #2196f3;
            }
            
            .notification-icon {
                display: inline-block;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                text-align: center;
                line-height: 32px;
                margin-right: 12px;
                color: white;
            }
            
            .notification-content {
                display: inline-block;
                vertical-align: top;
                width: calc(100% - 50px);
            }
            
            .notification-title {
                font-weight: 600;
                margin-bottom: 4px;
                font-size: 14px;
            }
            
            .notification-message {
                color: #666;
                font-size: 13px;
                margin-bottom: 4px;
            }
            
            .notification-time {
                color: #999;
                font-size: 12px;
            }
            
            .notification-footer {
                padding: 12px 16px;
                background: #f8f9fa;
                border-top: 1px solid #dee2e6;
                text-align: center;
            }
            
            .notification-loading,
            .notification-empty {
                padding: 20px;
                text-align: center;
                color: #666;
            }
            
            .bg-success { background-color: #28a745; }
            .bg-danger { background-color: #dc3545; }
            .bg-info { background-color: #17a2b8; }
            .bg-warning { background-color: #ffc107; }
            .bg-primary { background-color: #007bff; }
        `;
    document.head.appendChild(style);

    // Thêm widget vào header
    const headerRight = document.querySelector(
      ".header-right, .user-menu, .top-nav"
    );
    if (headerRight) {
      headerRight.insertAdjacentHTML("beforeend", notificationHTML);
    } else {
      // Fallback: thêm vào body
      document.body.insertAdjacentHTML("beforeend", notificationHTML);
    }
  }

  bindEvents() {
    const trigger = document.getElementById("notificationTrigger");
    const dropdown = document.getElementById("notificationDropdown");
    const markAllRead = document.getElementById("markAllRead");

    // Toggle dropdown
    trigger?.addEventListener("click", (e) => {
      e.stopPropagation();
      const isVisible = dropdown.style.display !== "none";
      dropdown.style.display = isVisible ? "none" : "block";

      if (!isVisible) {
        this.loadNotifications();
      }
    });

    // Đóng dropdown khi click outside
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".customer-notification-widget")) {
        dropdown.style.display = "none";
      }
    });

    // Đánh dấu tất cả đã đọc
    markAllRead?.addEventListener("click", () => {
      this.markAllAsRead();
    });
  }

  async loadNotifications() {
    try {
      const response = await fetch(`${this.apiUrl}?action=list&limit=10`);
      const data = await response.json();

      if (data.success) {
        this.updateNotificationBadge(data.unread_count);
        this.renderNotifications(data.notifications);
      }
    } catch (error) {
      console.error("Error loading notifications:", error);
    }
  }

  updateNotificationBadge(count) {
    const badge = document.getElementById("notificationBadge");
    if (badge) {
      if (count > 0) {
        badge.textContent = count > 99 ? "99+" : count;
        badge.style.display = "block";
      } else {
        badge.style.display = "none";
      }
    }
  }

  renderNotifications(notifications) {
    const list = document.getElementById("notificationList");
    if (!list) return;

    if (notifications.length === 0) {
      list.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Bạn chưa có thông báo nào.</p>
                </div>
            `;
      return;
    }

    list.innerHTML = notifications
      .map(
        (notification) => `
            <div class="notification-item ${
              !notification.is_read ? "unread" : ""
            }" 
                 data-id="${
                   notification.id
                 }" onclick="customerNotifications.markAsRead(${
          notification.id
        })">
                <div class="notification-icon bg-${notification.color}">
                    <i class="fas fa-${notification.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">
                        ${notification.title}
                        ${
                          !notification.is_read
                            ? '<span class="badge bg-primary ms-1">Mới</span>'
                            : ""
                        }
                    </div>
                    <div class="notification-message">
                        ${notification.message}
                    </div>
                    <div class="notification-time">
                        ${notification.created_at}
                    </div>
                </div>
            </div>
        `
      )
      .join("");
  }

  async markAsRead(notificationId) {
    try {
      const formData = new FormData();
      formData.append("notification_id", notificationId);

      const response = await fetch(`${this.apiUrl}?action=mark_read`, {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      if (data.success) {
        // Cập nhật UI
        const item = document.querySelector(`[data-id="${notificationId}"]`);
        if (item) {
          item.classList.remove("unread");
          const badge = item.querySelector(".badge");
          if (badge) badge.remove();
        }

        // Cập nhật số lượng
        this.loadNotifications();
      }
    } catch (error) {
      console.error("Error marking notification as read:", error);
    }
  }

  async markAllAsRead() {
    try {
      const response = await fetch(`${this.apiUrl}?action=mark_all_read`, {
        method: "POST",
      });

      const data = await response.json();
      if (data.success) {
        this.loadNotifications();
      }
    } catch (error) {
      console.error("Error marking all notifications as read:", error);
    }
  }

  startAutoUpdate() {
    setInterval(() => {
      // Chỉ cập nhật badge, không load full notifications
      this.updateUnreadCount();
    }, this.updateInterval);
  }

  async updateUnreadCount() {
    try {
      const response = await fetch(`${this.apiUrl}?action=count`);
      const data = await response.json();

      if (data.success) {
        this.updateNotificationBadge(data.unread_count);
      }
    } catch (error) {
      console.error("Error updating unread count:", error);
    }
  }
}

// Khởi tạo widget khi DOM ready
document.addEventListener("DOMContentLoaded", function () {
  // Kiểm tra xem user đã đăng nhập chưa
  if (
    document.querySelector(".user-menu, .header-user, [data-user-logged-in]")
  ) {
    window.customerNotifications = new CustomerNotifications();
  }
});
