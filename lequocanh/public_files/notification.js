// JavaScript xử lý thông báo
document.addEventListener("DOMContentLoaded", function () {
  // Lấy các phần tử DOM
  const notificationBtn = document.querySelector(".notification-btn");
  const notificationDropdown = document.querySelector(".notification-dropdown");
  const notificationBadge = document.querySelector(".notification-badge");

  if (!notificationBtn || !notificationDropdown) return;

  // Hàm cập nhật thông báo
  function updateNotifications() {
    console.log("Updating notifications...");
    // Try token API first, fallback to session API
    const tokenApiUrl =
      "./administrator/elements_LQA/mthongbao/getNotificationsToken.php?action=list";
    const sessionApiUrl =
      "./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=list";

    console.log("Trying token API first:", tokenApiUrl);

    fetch(tokenApiUrl, {
      credentials: "include",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then((response) => {
        console.log("Response status:", response.status);
        return response.json();
      })
      .then((data) => {
        console.log("Notification data:", data);
        if (data.success) {
          // Cập nhật badge thông báo
          if (notificationBadge) {
            if (data.unread_count > 0) {
              notificationBadge.textContent = data.unread_count;
              notificationBadge.style.display = "block";
            } else {
              notificationBadge.style.display = "none";
            }
          }

          // Cập nhật nội dung dropdown thông báo
          updateNotificationDropdown(data.notifications);
        } else {
          console.error("Token API returned error:", data.error);
          // Fallback to session API
          trySessionAPI();
        }
      })
      .catch((error) => {
        console.error("Token API failed:", error);
        // Fallback to session API
        trySessionAPI();
      });

    // Fallback function for session API
    function trySessionAPI() {
      console.log("Trying session API fallback:", sessionApiUrl);

      fetch(sessionApiUrl, {
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      })
        .then((response) => {
          console.log("Session API response status:", response.status);
          return response.json();
        })
        .then((data) => {
          console.log("Session API notification data:", data);
          if (data.success) {
            // Cập nhật badge thông báo
            if (notificationBadge) {
              if (data.unread_count > 0) {
                notificationBadge.textContent = data.unread_count;
                notificationBadge.style.display = "block";
              } else {
                notificationBadge.style.display = "none";
              }
            }

            // Cập nhật nội dung dropdown thông báo
            updateNotificationDropdown(data.notifications);
          } else {
            console.error("Session API returned error:", data.error);
            // Show empty state
            updateNotificationDropdown([]);
          }
        })
        .catch((error) => {
          console.error("Session API failed:", error);
          // Show empty state
          updateNotificationDropdown([]);
        });
    }
  }

  // Hàm cập nhật nội dung dropdown thông báo
  function updateNotificationDropdown(notifications) {
    console.log("updateNotificationDropdown called with:", notifications);
    const notificationList = document.querySelector(".notification-list");
    if (!notificationList) {
      console.error("notification-list element not found");
      return;
    }

    // Xóa nội dung cũ
    notificationList.innerHTML = "";

    if (!notifications || notifications.length === 0) {
      // Hiển thị thông báo trống
      notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="fas fa-bell-slash"></i>
                    <p>Bạn chưa có thông báo nào.</p>
                </div>
            `;
      return;
    }

    // Thêm các thông báo mới
    notifications.forEach((notification) => {
      const notificationItem = document.createElement("li");
      notificationItem.className = notification.is_read
        ? "notification-item"
        : "notification-item unread";
      notificationItem.dataset.id = notification.id;
      notificationItem.dataset.orderId = notification.order_id;

      notificationItem.innerHTML = `
                <div class="notification-icon bg-${notification.color}">
                    <i class="fas fa-${notification.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">
                        ${notification.title}
                        ${
                          !notification.is_read
                            ? '<span class="badge bg-primary">Mới</span>'
                            : ""
                        }
                    </div>
                    <div class="notification-info">
                        <p>${notification.message}</p>
                    </div>
                    <div class="notification-time">
                        ${notification.created_at}
                    </div>
                    <div class="notification-actions">
                        <button class="btn btn-sm btn-primary view-order-detail-btn" data-id="${
                          notification.order_id
                        }">
                            <i class="fas fa-eye"></i> Xem chi tiết đơn hàng
                        </button>
                        ${
                          !notification.is_read
                            ? `
                            <button class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="${notification.id}">
                                <i class="fas fa-check"></i> Đánh dấu đã đọc
                            </button>
                        `
                            : ""
                        }
                    </div>
                </div>
            `;

      notificationList.appendChild(notificationItem);

      // Thêm sự kiện cho nút đánh dấu đã đọc
      const markReadBtn = notificationItem.querySelector(".mark-read-btn");
      if (markReadBtn) {
        markReadBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          markNotificationAsRead(notification.id);
        });
      }
    });

    // Thêm sự kiện cho các nút đánh dấu đã đọc
    setupMarkReadButtons();
  }

  // Hàm thiết lập sự kiện cho các nút đánh dấu đã đọc và xem chi tiết
  function setupMarkReadButtons() {
    const markReadButtons = document.querySelectorAll(".mark-read-btn");
    markReadButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.stopPropagation();
        const notificationId = this.dataset.id;
        markNotificationAsRead(notificationId);
      });
    });

    // Nút đánh dấu tất cả đã đọc
    const markAllReadButton = document.querySelector(".mark-all-read");
    if (markAllReadButton) {
      markAllReadButton.addEventListener("click", function (e) {
        e.stopPropagation();
        markAllNotificationsAsRead();
      });
    }

    // Nút xóa thông báo đã đọc
    const deleteReadNotificationsButton = document.querySelector(
      ".delete-read-notifications"
    );
    if (deleteReadNotificationsButton) {
      deleteReadNotificationsButton.addEventListener("click", function (e) {
        e.stopPropagation();
        if (confirm("Bạn có chắc chắn muốn xóa tất cả thông báo đã đọc?")) {
          deleteReadNotifications();
        }
      });
    }

    // Nút xóa thông báo cụ thể
    const deleteNotificationButtons = document.querySelectorAll(
      ".delete-notification-btn"
    );
    deleteNotificationButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.stopPropagation();
        const notificationItem = this.closest(".notification-item");
        const orderId = this.dataset.id;

        if (confirm("Bạn có chắc chắn muốn xóa thông báo này?")) {
          deleteNotification(orderId, notificationItem);
        }
      });
    });

    // Nút xem chi tiết đơn hàng
    const viewOrderDetailButtons = document.querySelectorAll(
      ".view-order-detail-btn"
    );
    viewOrderDetailButtons.forEach((button) => {
      button.addEventListener("click", function (e) {
        e.stopPropagation();
        const orderId = this.dataset.id;
        showOrderDetail(orderId);
      });
    });
  }

  // Hàm hiển thị chi tiết đơn hàng
  function showOrderDetail(orderId) {
    // Đóng dropdown thông báo
    notificationDropdown.classList.remove("show");

    // Hiển thị loading
    const orderDetailModal = document.querySelector(".order-detail-modal");
    const orderItems = document.getElementById("order-items");

    orderItems.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Đang tải thông tin đơn hàng...
                </td>
            </tr>
        `;

    // Hiển thị modal
    orderDetailModal.classList.add("show");

    // Lấy thông tin chi tiết đơn hàng
    fetch(
      `./administrator/elements_LQA/mthongbao/getOrderDetail.php?id=${orderId}`
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Cập nhật thông tin đơn hàng
          const order = data.order;

          document.getElementById("order-id").textContent = order.id;
          document.getElementById("order-code").textContent = order.order_code;
          document.getElementById("order-date").textContent = order.created_at;
          document.getElementById("order-payment-method").textContent =
            order.payment_method;
          document.getElementById("order-address").textContent =
            order.shipping_address || "Không có thông tin";
          document.getElementById("order-status").textContent =
            order.status_text;
          document.getElementById(
            "order-status"
          ).className = `order-status ${order.status_class}`;
          document.getElementById("order-total").textContent =
            new Intl.NumberFormat("vi-VN").format(order.total_amount) + " đ";

          // Cập nhật danh sách sản phẩm
          let itemsHtml = "";

          if (order.items.length === 0) {
            itemsHtml = `
                            <tr>
                                <td colspan="5" class="text-center">Không có sản phẩm nào</td>
                            </tr>
                        `;
          } else {
            order.items.forEach((item) => {
              const imagePath = item.product_image
                ? `./administrator/images_LQA/${item.product_image}`
                : "./administrator/images_LQA/no-image.png";

              itemsHtml += `
                                <tr>
                                    <td>
                                        <img src="${imagePath}" alt="${
                item.product_name
              }" class="product-image">
                                    </td>
                                    <td class="product-name">${
                                      item.product_name
                                    }</td>
                                    <td>${new Intl.NumberFormat("vi-VN").format(
                                      item.price
                                    )} đ</td>
                                    <td>${item.quantity}</td>
                                    <td>${new Intl.NumberFormat("vi-VN").format(
                                      item.total
                                    )} đ</td>
                                </tr>
                            `;
            });
          }

          orderItems.innerHTML = itemsHtml;

          // Cập nhật số lượng thông báo chưa đọc
          updateNotifications();
        } else {
          // Hiển thị thông báo lỗi
          orderItems.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-danger">
                                <i class="fas fa-exclamation-circle"></i> ${
                                  data.message ||
                                  "Có lỗi xảy ra khi lấy thông tin đơn hàng"
                                }
                            </td>
                        </tr>
                    `;
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
        orderItems.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            <i class="fas fa-exclamation-circle"></i> Có lỗi xảy ra khi lấy thông tin đơn hàng
                        </td>
                    </tr>
                `;
      });
  }

  // Hàm đánh dấu một thông báo đã đọc
  function markNotificationAsRead(notificationId) {
    const formData = new FormData();
    formData.append("notification_id", notificationId);

    fetch(
      `./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=mark_read`,
      {
        method: "POST",
        body: formData,
        credentials: "same-origin",
      }
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Cập nhật giao diện
          const notificationItem = document.querySelector(
            `.notification-item[data-id="${notificationId}"]`
          );
          if (notificationItem) {
            notificationItem.classList.remove("unread");
            const markReadBtn =
              notificationItem.querySelector(".mark-read-btn");
            if (markReadBtn) {
              markReadBtn.remove();
            }
            const badge = notificationItem.querySelector(".badge");
            if (badge) {
              badge.remove();
            }
          }

          // Cập nhật số lượng thông báo chưa đọc
          updateNotifications();
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
      });
  }

  // Hàm đánh dấu tất cả thông báo đã đọc
  function markAllNotificationsAsRead() {
    fetch(
      `./administrator/elements_LQA/mthongbao/getCustomerNotifications.php?action=mark_all_read`,
      {
        method: "POST",
        credentials: "same-origin",
      }
    )
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Cập nhật giao diện
          document
            .querySelectorAll(".notification-item.unread")
            .forEach((item) => {
              item.classList.remove("unread");
            });

          document.querySelectorAll(".mark-read-btn").forEach((btn) => {
            btn.remove();
          });

          document
            .querySelectorAll(".notification-title .badge")
            .forEach((badge) => {
              badge.remove();
            });

          // Cập nhật số lượng thông báo chưa đọc
          updateNotifications();
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
      });
  }

  // Hàm xóa tất cả thông báo đã đọc
  function deleteReadNotifications() {
    fetch("./administrator/elements_LQA/mthongbao/getNotifications.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "delete_read_notifications=1",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Cập nhật giao diện bằng cách tải lại danh sách thông báo
          updateNotifications();
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
      });
  }

  // Hàm xóa một thông báo cụ thể
  function deleteNotification(orderId, notificationItem) {
    fetch("./administrator/elements_LQA/mthongbao/getNotifications.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `delete_notification=1&order_id=${orderId}`,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Xóa thông báo khỏi giao diện
          if (notificationItem) {
            notificationItem.remove();
          }

          // Kiểm tra xem còn thông báo nào không
          const notificationList = document.querySelector(".notification-list");
          if (notificationList && notificationList.children.length === 0) {
            notificationList.innerHTML = `
                        <li class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>Bạn chưa có thông báo nào.</p>
                        </li>
                    `;
          }

          // Cập nhật số lượng thông báo chưa đọc
          updateNotifications();
        }
      })
      .catch((error) => {
        console.error("Lỗi:", error);
      });
  }

  // Hiển thị/ẩn dropdown khi nhấn vào nút thông báo
  notificationBtn.addEventListener("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    // Toggle dropdown
    notificationDropdown.classList.toggle("show");

    // Nếu dropdown đang hiển thị, cập nhật nội dung
    if (notificationDropdown.classList.contains("show")) {
      updateNotifications();
    }
  });

  // Đóng dropdown khi nhấn ra ngoài
  document.addEventListener("click", function (e) {
    if (
      notificationDropdown.classList.contains("show") &&
      !notificationDropdown.contains(e.target) &&
      !notificationBtn.contains(e.target)
    ) {
      notificationDropdown.classList.remove("show");
    }
  });

  // Ngăn sự kiện click trong dropdown lan ra ngoài
  notificationDropdown.addEventListener("click", function (e) {
    e.stopPropagation();
  });

  // Xử lý đóng modal chi tiết đơn hàng
  const orderDetailModal = document.querySelector(".order-detail-modal");
  const orderDetailClose = document.querySelector(".order-detail-close");

  if (orderDetailModal && orderDetailClose) {
    // Đóng modal khi nhấn nút đóng
    orderDetailClose.addEventListener("click", function () {
      orderDetailModal.classList.remove("show");
    });

    // Đóng modal khi nhấn ra ngoài
    orderDetailModal.addEventListener("click", function (e) {
      if (e.target === orderDetailModal) {
        orderDetailModal.classList.remove("show");
      }
    });

    // Đóng modal khi nhấn phím Escape
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && orderDetailModal.classList.contains("show")) {
        orderDetailModal.classList.remove("show");
      }
    });
  }

  // Cập nhật số lượng thông báo khi trang được tải
  updateNotifications();

  // Cập nhật số lượng thông báo mỗi 30 giây
  setInterval(updateNotifications, 30000);
});
