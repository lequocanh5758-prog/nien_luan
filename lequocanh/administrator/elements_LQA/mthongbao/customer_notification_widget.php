<?php
// Customer Notification Widget
// Include this file where you want to display notifications

require_once __DIR__ . '/../mod/sessionManager.php';
require_once __DIR__ . '/../mod/CustomerNotificationManager.php';

// Start session safely
SessionManager::start();

// Check if user is logged in
if (!isset($_SESSION['USER'])) {
    return; // Don't show notifications if not logged in
}

$userId = $_SESSION['USER'];
$notificationManager = new CustomerNotificationManager();
$unreadCount = $notificationManager->getUnreadCount($userId);
$notifications = $notificationManager->getUserNotifications($userId, 5); // Get latest 5 notifications
?>

<style>
.notification-widget {
    position: relative;
    display: inline-block;
}

.notification-bell {
    position: relative;
    cursor: pointer;
    font-size: 20px;
    color: #333;
}

.notification-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: none;
    z-index: 1000;
    margin-top: 10px;
}

.notification-dropdown.show {
    display: block;
}

.notification-header {
    padding: 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h5 {
    margin: 0;
    font-size: 16px;
}

.mark-all-read {
    font-size: 12px;
    color: #007bff;
    cursor: pointer;
    text-decoration: none;
}

.mark-all-read:hover {
    text-decoration: underline;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
}

.notification-item .notification-title {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 14px;
}

.notification-item .notification-message {
    font-size: 13px;
    color: #666;
    margin-bottom: 5px;
}

.notification-item .notification-time {
    font-size: 11px;
    color: #999;
}

.notification-footer {
    padding: 10px;
    text-align: center;
    border-top: 1px solid #eee;
}

.notification-footer a {
    color: #007bff;
    text-decoration: none;
    font-size: 14px;
}

.notification-footer a:hover {
    text-decoration: underline;
}

.no-notifications {
    padding: 30px;
    text-align: center;
    color: #999;
}
</style>

<div class="notification-widget">
    <div class="notification-bell" onclick="toggleNotifications()">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="notification-count"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
        <?php endif; ?>
    </div>
    
    <div id="notificationDropdown" class="notification-dropdown">
        <div class="notification-header">
            <h5>Thông báo</h5>
            <?php if ($unreadCount > 0): ?>
                <a href="#" class="mark-all-read" onclick="markAllAsRead(); return false;">Đánh dấu tất cả đã đọc</a>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash" style="font-size: 40px; margin-bottom: 10px;"></i>
                    <p>Không có thông báo nào</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                         onclick="markAsRead(<?php echo $notification['id']; ?>)">
                        <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                        <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                        <div class="notification-time">
                            <?php 
                            $time = strtotime($notification['created_at']);
                            $timeDiff = time() - $time;
                            
                            if ($timeDiff < 60) {
                                echo 'Vừa xong';
                            } elseif ($timeDiff < 3600) {
                                echo floor($timeDiff / 60) . ' phút trước';
                            } elseif ($timeDiff < 86400) {
                                echo floor($timeDiff / 3600) . ' giờ trước';
                            } else {
                                echo date('d/m/Y H:i', $time);
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="notification-footer">
            <a href="index.php?req=notifications">Xem tất cả thông báo</a>
        </div>
    </div>
</div>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const widget = document.querySelector('.notification-widget');
    if (!widget.contains(event.target)) {
        document.getElementById('notificationDropdown').classList.remove('show');
    }
});

function markAsRead(notificationId) {
    fetch('elements_LQA/mthongbao/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to update notification count
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    fetch('elements_LQA/mthongbao/mark_all_notifications_read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload to update notifications
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    // You can implement AJAX refresh here if needed
}, 30000);
</script>
