# Customer Notification System Fix Guide

## Summary of Changes Made

1. **Removed notification widget from order history page** - The notification bell now only appears on the index page as requested.

2. **Fixed JavaScript conflicts** - Removed duplicate `customer_notifications.js` file that was conflicting with `notification.js`.

3. **Added debug logging** - Added console logs and error logs to help diagnose issues.

## Steps to Test and Fix Notifications

### Step 1: Setup Database Table
First, ensure the notifications table exists:
```
http://localhost/lequocanh/setup_notifications_table.php
```

### Step 2: Test Notification Creation
Visit this page to manually create a test notification:
```
http://localhost/lequocanh/test_create_notification.php
```

### Step 3: Check Console on Index Page
1. Open the index page: `http://localhost/lequocanh/index.php`
2. Open browser developer tools (F12)
3. Go to the Console tab
4. Look for messages like:
   - "Updating notification count..."
   - "Notification data: ..."
   - Any error messages

### Step 4: Check PHP Error Logs
After approving a COD order, check your PHP error logs for messages containing:
- "CustomerNotificationManager:"
- "getCustomerNotifications:"

## Common Issues and Solutions

### Issue 1: Notifications not appearing
**Possible causes:**
- Database table doesn't exist
- User ID mismatch between session and database

**Solution:**
1. Run the setup script (Step 1)
2. Check that `$_SESSION['USER']` matches the `ma_nguoi_dung` in `don_hang` table

### Issue 2: JavaScript errors
**Possible causes:**
- Elements not found
- API returning errors

**Solution:**
1. Check console for specific error messages
2. Ensure you're logged in when testing

### Issue 3: Wrong notification count
**Possible causes:**
- Old notifications not marked as read
- Database query issues

**Solution:**
1. Use the test page to view all notifications
2. Mark all as read using the dropdown menu

## How the System Works

1. **When an order is approved:**
   - `orders.php` calls `notifyOrderApproved()`
   - A notification is inserted into `customer_notifications` table
   - The `user_id` field stores the username from `ma_nguoi_dung`

2. **On the index page:**
   - JavaScript polls `/administrator/elements_LQA/mthongbao/getCustomerNotifications.php`
   - The API returns notifications for the logged-in user
   - The bell icon shows the unread count
   - Clicking the bell shows the dropdown

3. **Notification features:**
   - View order details
   - Mark as read
   - Mark all as read
   - Delete read notifications

## Testing Order Approval

1. Login as a customer who has a COD order
2. Login as admin in another browser/incognito
3. Approve the COD order
4. Switch back to customer browser
5. The notification should appear within 30 seconds (or refresh the page)

## Files Involved

- `/index.php` - Main page with notification bell
- `/public_files/notification.js` - JavaScript handling notifications
- `/administrator/elements_LQA/mthongbao/getCustomerNotifications.php` - API endpoint
- `/administrator/elements_LQA/mod/CustomerNotificationManager.php` - Notification logic
- `/administrator/elements_LQA/madmin/orders.php` - Order approval logic

## Cleanup Scripts Created

These test scripts can be deleted after fixing the issue:
- `/test_customer_notifications.php`
- `/test_create_notification.php`
- `/setup_notifications_table.php`
- `/test_notifications.php` (if exists)

## Next Steps

1. Follow the testing steps above
2. Check logs for any errors
3. If notifications still don't work, check:
   - Session variables
   - Database user IDs
   - JavaScript console errors
