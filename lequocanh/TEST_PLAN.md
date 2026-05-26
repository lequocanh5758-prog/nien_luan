# Comprehensive Test Plan
## Date: 2026-05-12

---

## 📊 Overview

| Category | Pages | Features |
|----------|-------|----------|
| Frontend | 18 | User-facing pages |
| Admin | 50+ | Management pages |
| API | 16 | REST endpoints |
| Components | 8 | Shared components |
| **Total** | **90+** | **200+ features** |

---

## 🎯 Frontend Pages

### 1. Homepage (`index.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 1.1 | Page load | Navigate to `/` | Page loads with products |
| 1.2 | Carousel | Check banner carousel | Carousel rotates |
| 1.3 | Featured products | Check "Sản Phẩm Nổi Bật" | Products displayed |
| 1.4 | New products | Check "Sản Phẩm Mới" | Products displayed |
| 1.5 | Sale products | Check "Khuyến Mãi Hot" | Products displayed |
| 1.6 | Product list | Scroll to product grid | 80 products shown |
| 1.7 | Pagination | Check if pagination exists | Works correctly |
| 1.8 | Filter sidebar | Click "Bộ lọc" | Filter panel opens |
| 1.9 | Price filter | Set min/max price | Products filtered |
| 1.10 | Color filter | Select color | Products filtered |
| 1.11 | Rating filter | Select rating | Products filtered |
| 1.12 | Clear filter | Click "Xóa bộ lọc" | Filters cleared |
| 1.13 | Product card | Hover product card | Hover effect works |
| 1.14 | Compare checkbox | Select products | Checkbox works |
| 1.15 | Compare button | Click "So sánh" | Redirects to comparison |

### 2. Product Detail (`?reqHanghoa=ID`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 2.1 | Page load | Navigate to product | Page loads |
| 2.2 | Product image | Check image display | Image shown |
| 2.3 | Product name | Check name display | Name shown |
| 2.4 | Product price | Check price display | Price shown |
| 2.5 | Discount price | Check discount | Discount shown |
| 2.6 | Discount badge | Check badge | Badge shown |
| 2.7 | Description | Check description | Description shown |
| 2.8 | Brand | Check brand | Brand shown |
| 2.9 | Stock status | Check stock | Status shown |
| 2.10 | Specifications | Check specs | Specs shown |
| 2.11 | Rating | Check rating | Stars shown |
| 2.12 | Review count | Check count | Count shown |
| 2.13 | Add to cart | Click "Thêm vào giỏ" | Item added |
| 2.14 | Buy now | Click "Mua ngay" | Redirects to checkout |
| 2.15 | Back button | Click "Quay lại" | Goes back |
| 2.16 | Related products | Scroll down | Related shown |
| 2.17 | Review section | Check reviews | Reviews shown |
| 2.18 | Write review | Submit review | Review saved |

### 3. Search (`search.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 3.1 | Page load | Navigate to search | Page loads |
| 3.2 | Search input | Enter keyword | Input works |
| 3.3 | Search submit | Submit search | Results shown |
| 3.4 | Results display | Check results | Products shown |
| 3.5 | No results | Search invalid term | "Không tìm thấy" |
| 3.6 | Product click | Click product | Redirects to detail |

### 4. Category View (`?reqView=ID`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 4.1 | Page load | Navigate to category | Page loads |
| 4.2 | Category name | Check header | Name shown |
| 4.3 | Product list | Check products | Products shown |
| 4.4 | Filter | Apply filter | Products filtered |
| 4.5 | Sort | Change sort | Products sorted |

### 5. Product Comparison (`sosanh.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 5.1 | Page load | Navigate to comparison | Page loads |
| 5.2 | Product 1 | Check first product | Details shown |
| 5.3 | Product 2 | Check second product | Details shown |
| 5.4 | Specs comparison | Check specs table | Specs compared |
| 5.5 | Price comparison | Check prices | Prices shown |
| 5.6 | Add to cart | Click "Thêm vào giỏ" | Item added |
| 5.7 | View detail | Click "Xem chi tiết" | Redirects |

### 6. Blog (`blog.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 6.1 | Page load | Navigate to blog | Page loads |
| 6.2 | Blog list | Check articles | Articles shown |
| 6.3 | Article click | Click article | Redirects to detail |

### 7. News Detail (`news_detail.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 7.1 | Page load | Navigate to news | Page loads |
| 7.2 | News content | Check content | Content shown |
| 7.3 | Related news | Check related | Related shown |

### 8. All News (`all_news.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 8.1 | Page load | Navigate to all news | Page loads |
| 8.2 | News list | Check articles | Articles shown |
| 8.3 | Pagination | Check pagination | Works correctly |

### 9. Static Page (`page.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 9.1 | Page load | Navigate to page | Page loads |
| 9.2 | Page content | Check content | Content shown |
| 9.3 | Navigation | Check breadcrumb | Navigation works |

### 10. Order Tracking (`track_order.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 10.1 | Page load | Navigate to tracking | Page loads |
| 10.2 | Order search | Enter order code | Search works |
| 10.3 | Order status | Check status | Status shown |
| 10.4 | Order details | Check details | Details shown |

---

## 🔐 Authentication

### 11. Login (`administrator/userLogin.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 11.1 | Page load | Navigate to login | Page loads |
| 11.2 | Username input | Enter username | Input works |
| 11.3 | Password input | Enter password | Input works |
| 11.4 | Login submit | Submit form | Login success |
| 11.5 | Invalid credentials | Wrong password | Error shown |
| 11.6 | Remember me | Check remember | Checkbox works |
| 11.7 | Forgot password | Click link | Redirects |
| 11.8 | Register link | Click link | Redirects |

### 12. Registration (`administrator/signUp.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 12.1 | Page load | Navigate to register | Page loads |
| 12.2 | Username | Enter username | Validation works |
| 12.3 | Password | Enter password | Validation works |
| 12.4 | Confirm password | Enter confirm | Validation works |
| 12.5 | Full name | Enter name | Input works |
| 12.6 | Phone | Enter phone | Validation works |
| 12.7 | Email | Enter email | Validation works |
| 12.8 | Gender | Select gender | Select works |
| 12.9 | Birthday | Enter date | Input works |
| 12.10 | Province | Select province | Select works |
| 12.11 | District | Select district | Select works |
| 12.12 | Ward | Select ward | Select works |
| 12.13 | Address | Enter address | Input works |
| 12.14 | Submit | Submit form | Registration success |
| 12.15 | Duplicate username | Use existing username | Error shown |
| 12.16 | Duplicate phone | Use existing phone | Error shown |

### 13. Logout
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 13.1 | Logout | Click logout | Session destroyed |
| 13.2 | Redirect | After logout | Redirects to login |

---

## 🛒 Shopping Cart

### 14. Cart View (`administrator/elements_LQA/mgiohang/giohangView.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 14.1 | Page load | Navigate to cart | Page loads |
| 14.2 | Empty cart | Check empty cart | "Giỏ hàng trống" |
| 14.3 | Cart items | Check items | Items shown |
| 14.4 | Product image | Check image | Image shown |
| 14.5 | Product name | Check name | Name shown |
| 14.6 | Product price | Check price | Price shown |
| 14.7 | Quantity | Check quantity | Quantity shown |
| 14.8 | Subtotal | Check subtotal | Subtotal shown |
| 14.9 | Total | Check total | Total shown |
| 14.10 | Increase quantity | Click "+" | Quantity increases |
| 14.11 | Decrease quantity | Click "-" | Quantity decreases |
| 14.12 | Remove item | Click remove | Item removed |
| 14.13 | Select all | Click "Chọn tất cả" | All selected |
| 14.14 | Select item | Click checkbox | Item selected |
| 14.15 | Delete selected | Click "Xóa đã chọn" | Items deleted |
| 14.16 | Continue shopping | Click link | Redirects |
| 14.17 | Checkout | Click "Mua hàng" | Redirects |

### 15. Add to Cart (`administrator/elements_LQA/mgiohang/giohangAct.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 15.1 | Add item | POST add action | Item added |
| 15.2 | Update quantity | POST update action | Quantity updated |
| 15.3 | Remove item | POST remove action | Item removed |
| 15.4 | Clear cart | POST clear action | Cart cleared |
| 15.5 | Stock check | Add out-of-stock | Error shown |
| 15.6 | Login required | Add without login | Redirects to login |

### 16. Checkout (`administrator/elements_LQA/mgiohang/checkout.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 16.1 | Page load | Navigate to checkout | Page loads |
| 16.2 | Address form | Check form | Form shown |
| 16.3 | Saved address | Check saved | Address shown |
| 16.4 | Province select | Select province | Options load |
| 16.5 | District select | Select district | Options load |
| 16.6 | Ward select | Select ward | Options load |
| 16.7 | Address input | Enter address | Input works |
| 16.8 | Shipping method | Select method | Method selected |
| 16.9 | Coupon code | Enter coupon | Coupon applied |
| 16.10 | Order summary | Check summary | Summary shown |
| 16.11 | Payment method | Select method | Method selected |
| 16.12 | MoMo payment | Select MoMo | MoMo shown |
| 16.13 | Bank transfer | Select bank | Bank shown |
| 16.14 | COD | Select COD | COD shown |
| 16.15 | Confirm order | Click confirm | Order created |
| 16.16 | Order success | Check success | Success shown |

---

## 👤 User Profile

### 17. Profile View (`administrator/elements_LQA/mUser/userProfile.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 17.1 | Page load | Navigate to profile | Page loads |
| 17.2 | User info | Check info | Info shown |
| 17.3 | Edit profile | Click edit | Form shown |
| 17.4 | Save profile | Submit form | Profile saved |
| 17.5 | Change password | Click change | Form shown |
| 17.6 | Save password | Submit form | Password changed |

### 18. Order History
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 18.1 | Order list | Check orders | Orders shown |
| 18.2 | Order detail | Click order | Details shown |
| 18.3 | Order status | Check status | Status shown |
| 18.4 | Track order | Click track | Tracking shown |

---

## 🔧 Admin Panel

### 19. Admin Dashboard (`administrator/index.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 19.1 | Page load | Navigate to admin | Page loads |
| 19.2 | Welcome message | Check message | Message shown |
| 19.3 | User info | Check info | Info shown |
| 19.4 | Menu | Check menu | Menu shown |
| 19.5 | Navigation | Click menu items | Navigation works |

### 20. Product Management (`administrator/elements_LQA/mhanghoa/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 20.1 | Product list | Navigate to products | List shown |
| 20.2 | Add product | Click add | Form shown |
| 20.3 | Product name | Enter name | Input works |
| 20.4 | Description | Enter desc | Input works |
| 20.5 | Price | Enter price | Input works |
| 20.6 | Category | Select category | Select works |
| 20.7 | Brand | Select brand | Select works |
| 20.8 | Unit | Select unit | Select works |
| 20.9 | Image upload | Upload image | Image uploaded |
| 20.10 | Save product | Submit form | Product saved |
| 20.11 | Edit product | Click edit | Form shown |
| 20.12 | Update product | Submit form | Product updated |
| 20.13 | Delete product | Click delete | Product deleted |
| 20.14 | Search product | Enter search | Results shown |
| 20.15 | Filter products | Apply filter | Products filtered |
| 20.16 | Product status | Change status | Status changed |
| 20.17 | Image management | Manage images | Images managed |

### 21. Image Management (`administrator/elements_LQA/mhinhanh/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 21.1 | Image list | Navigate to images | List shown |
| 21.2 | Upload image | Upload file | Image uploaded |
| 21.3 | Image preview | Check preview | Preview shown |
| 21.4 | Delete image | Click delete | Image deleted |
| 21.5 | Apply to product | Click apply | Image applied |
| 21.6 | Bulk delete | Select & delete | Images deleted |
| 21.7 | Search images | Enter search | Results shown |

### 22. Order Management (`administrator/elements_LQA/madmin/orders.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 22.1 | Order list | Navigate to orders | List shown |
| 22.2 | Order detail | Click view | Details shown |
| 22.3 | Approve order | Click approve | Order approved |
| 22.4 | Cancel order | Click cancel | Order cancelled |
| 22.5 | Confirm delivery | Click confirm | Delivery confirmed |
| 22.6 | Complete order | Click complete | Order completed |
| 22.7 | Print invoice | Click print | Invoice printed |
| 22.8 | Search orders | Enter search | Results shown |
| 22.9 | Filter by status | Select status | Orders filtered |
| 22.10 | Export PDF | Click export | PDF exported |
| 22.11 | Export Excel | Click export | Excel exported |

### 23. Customer Management (`administrator/elements_LQA/mkhachhang/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 23.1 | Customer list | Navigate | List shown |
| 23.2 | Customer detail | Click view | Details shown |
| 23.3 | Edit customer | Click edit | Form shown |
| 23.4 | Save customer | Submit form | Customer saved |
| 23.5 | Search customer | Enter search | Results shown |

### 24. Price Management (`administrator/elements_LQA/mdongia/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 24.1 | Price list | Navigate | List shown |
| 24.2 | Add price | Click add | Form shown |
| 24.3 | Edit price | Click edit | Form shown |
| 24.4 | Save price | Submit form | Price saved |
| 24.5 | Delete price | Click delete | Price deleted |
| 24.6 | Price statistics | Check stats | Stats shown |

### 25. Import Management (`administrator/elements_LQA/mmphieunhap/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 25.1 | Import list | Navigate | List shown |
| 25.2 | Add import | Click add | Form shown |
| 25.3 | Edit import | Click edit | Form shown |
| 25.4 | Save import | Submit form | Import saved |
| 25.5 | Import detail | Click view | Details shown |

### 26. Inventory Management (`administrator/elements_LQA/mmtonkho/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 26.1 | Inventory list | Navigate | List shown |
| 26.2 | Edit inventory | Click edit | Form shown |
| 26.3 | Save inventory | Submit form | Inventory saved |
| 26.4 | Stock check | Check stock | Stock shown |

### 27. Reports (`administrator/elements_LQA/mbaocao/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 27.1 | Revenue report | Navigate | Report shown |
| 27.2 | Profit report | Navigate | Report shown |
| 27.3 | Best sellers | Navigate | Report shown |
| 27.4 | Date filter | Select dates | Report filtered |
| 27.5 | Export report | Click export | Report exported |

### 28. Notifications (`administrator/elements_LQA/mthongbao/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 28.1 | Notification list | Navigate | List shown |
| 28.2 | Mark read | Click mark | Marked read |
| 28.3 | Delete notification | Click delete | Deleted |
| 28.4 | Notification badge | Check badge | Badge shown |

### 29. Featured Products (`administrator/elements_LQA/msanphamnoibat/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 29.1 | Featured list | Navigate | List shown |
| 29.2 | Set featured | Click set | Product featured |
| 29.3 | Unset featured | Click unset | Product unfeatured |
| 29.4 | Set new | Click set | Product marked new |
| 29.5 | Set sale | Click set | Product on sale |
| 29.6 | Remove sale | Click remove | Sale removed |

### 30. Activity Log (`administrator/elements_LQA/mnhatkyhoatdong/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 30.1 | Log list | Navigate | List shown |
| 30.2 | Log detail | Click view | Details shown |
| 30.3 | Filter logs | Apply filter | Logs filtered |
| 30.4 | Search logs | Enter search | Results shown |

### 31. Permissions (`administrator/elements_LQA/mphanquyen/`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 31.1 | Permission list | Navigate | List shown |
| 31.2 | Edit permission | Click edit | Form shown |
| 31.3 | Save permission | Submit form | Permission saved |
| 31.4 | Role management | Manage roles | Roles managed |

### 32. Coupons (`administrator/elements_LQA/madmin/couponView.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 32.1 | Coupon list | Navigate | List shown |
| 32.2 | Add coupon | Click add | Form shown |
| 32.3 | Edit coupon | Click edit | Form shown |
| 32.4 | Save coupon | Submit form | Coupon saved |
| 32.5 | Delete coupon | Click delete | Coupon deleted |
| 32.6 | Coupon status | Change status | Status changed |

### 33. Shipping (`administrator/elements_LQA/madmin/shipping_config.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 33.1 | Shipping config | Navigate | Config shown |
| 33.2 | Edit config | Click edit | Form shown |
| 33.3 | Save config | Submit form | Config saved |
| 33.4 | Shipping methods | Manage methods | Methods managed |

### 34. Payment Config (`administrator/elements_LQA/madmin/payment_config.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 34.1 | Payment config | Navigate | Config shown |
| 34.2 | Edit config | Click edit | Form shown |
| 34.3 | Save config | Submit form | Config saved |
| 34.4 | Bank info | Edit bank info | Info saved |
| 34.5 | MoMo config | Edit MoMo | Config saved |

### 35. Banners (`administrator/elements_LQA/madmin/banners.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 35.1 | Banner list | Navigate | List shown |
| 35.2 | Add banner | Click add | Form shown |
| 35.3 | Edit banner | Click edit | Form shown |
| 35.4 | Save banner | Submit form | Banner saved |
| 35.5 | Delete banner | Click delete | Banner deleted |
| 35.6 | Banner status | Change status | Status changed |

### 36. News Management (`administrator/elements_LQA/madmin/news.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 36.1 | News list | Navigate | List shown |
| 36.2 | Add news | Click add | Form shown |
| 36.3 | Edit news | Click edit | Form shown |
| 36.4 | Save news | Submit form | News saved |
| 36.5 | Delete news | Click delete | News deleted |
| 36.6 | News status | Change status | Status changed |

### 37. Promotions (`administrator/elements_LQA/madmin/promotions.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 37.1 | Promotion list | Navigate | List shown |
| 37.2 | Add promotion | Click add | Form shown |
| 37.3 | Edit promotion | Click edit | Form shown |
| 37.4 | Save promotion | Submit form | Promotion saved |
| 37.5 | Delete promotion | Click delete | Promotion deleted |

---

## 🔌 API Endpoints

### 38. Product API (`api/v2/`)
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 38.1 | /products | GET | List products | Products returned |
| 38.2 | /products/:id | GET | Get product | Product returned |
| 38.3 | /products | POST | Create product | Product created |
| 38.4 | /products/:id | PUT | Update product | Product updated |
| 38.5 | /products/:id | DELETE | Delete product | Product deleted |
| 38.6 | /products/search | GET | Search products | Results returned |
| 38.7 | /products/filter | GET | Filter products | Results returned |

### 39. Cart API
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 39.1 | /cart | GET | Get cart | Cart returned |
| 39.2 | /cart/add | POST | Add to cart | Item added |
| 39.3 | /cart/update | POST | Update cart | Cart updated |
| 39.4 | /cart/remove | POST | Remove item | Item removed |
| 39.5 | /cart/clear | POST | Clear cart | Cart cleared |
| 39.6 | /cart/count | GET | Get count | Count returned |

### 40. Order API
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 40.1 | /orders | GET | List orders | Orders returned |
| 40.2 | /orders/:id | GET | Get order | Order returned |
| 40.3 | /orders | POST | Create order | Order created |
| 40.4 | /orders/:id/status | PUT | Update status | Status updated |

### 41. User API
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 41.1 | /user/profile | GET | Get profile | Profile returned |
| 41.2 | /user/profile | PUT | Update profile | Profile updated |
| 41.3 | /user/orders | GET | Get orders | Orders returned |

### 42. Search API
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 42.1 | /search | GET | Search | Results returned |
| 42.2 | /search/suggestions | GET | Suggestions | Suggestions returned |

### 43. Filter API
| # | Endpoint | Method | Test Case | Expected Result |
|---|----------|--------|-----------|-----------------|
| 43.1 | /filter/options | GET | Get options | Options returned |
| 43.2 | /filter/products | GET | Filter products | Results returned |

---

## 🧩 Components

### 44. Navbar (`components/navbar.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 44.1 | Logo | Check logo | Logo shown |
| 44.2 | Search bar | Check search | Search works |
| 44.3 | Cart icon | Check cart | Cart shown |
| 44.4 | Cart count | Check count | Count shown |
| 44.5 | User menu | Check menu | Menu shown |
| 44.6 | Login link | Check link | Link works |
| 44.7 | Logout link | Check link | Link works |
| 44.8 | Category menu | Check menu | Menu shown |
| 44.9 | Mobile menu | Check mobile | Menu works |

### 45. Footer (`components/footer.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 45.1 | Company info | Check info | Info shown |
| 45.2 | Links | Check links | Links work |
| 45.3 | Contact info | Check info | Info shown |
| 45.4 | Social links | Check links | Links work |
| 45.5 | Newsletter | Check form | Form works |
| 45.6 | Payment icons | Check icons | Icons shown |

### 46. Product Card
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 46.1 | Product image | Check image | Image shown |
| 46.2 | Product name | Check name | Name shown |
| 46.3 | Product price | Check price | Price shown |
| 46.4 | Discount badge | Check badge | Badge shown |
| 46.5 | Rating stars | Check stars | Stars shown |
| 46.6 | Add to cart | Click button | Item added |
| 46.7 | View detail | Click link | Redirects |

### 47. Review Display (`components/product_review_display.php`)
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 47.1 | Review list | Check reviews | Reviews shown |
| 47.2 | Review form | Check form | Form shown |
| 47.3 | Submit review | Submit form | Review saved |
| 47.4 | Rating input | Select rating | Rating works |
| 47.5 | Comment input | Enter comment | Input works |

---

## 🔒 Security Tests

### 48. CSRF Protection
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 48.1 | CSRF token | Check token | Token present |
| 48.2 | Invalid token | Submit invalid | Request rejected |
| 48.3 | Missing token | Submit without | Request rejected |

### 49. SQL Injection
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 49.1 | Search input | Enter SQL injection | Input sanitized |
| 49.2 | Login input | Enter injection | Input sanitized |
| 49.3 | Form input | Enter injection | Input sanitized |

### 50. XSS Protection
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 50.1 | Script injection | Enter script | Script escaped |
| 50.2 | HTML injection | Enter HTML | HTML escaped |
| 50.3 | Event injection | Enter event | Event escaped |

### 51. Authentication
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 51.1 | Session management | Check session | Session works |
| 51.2 | Session timeout | Wait for timeout | Session expires |
| 51.3 | Unauthorized access | Access admin | Redirects to login |
| 51.4 | Role-based access | Check permissions | Permissions enforced |

---

## 📱 Responsive Design

### 52. Mobile View
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 52.1 | Homepage | View on mobile | Responsive |
| 52.2 | Product detail | View on mobile | Responsive |
| 52.3 | Cart | View on mobile | Responsive |
| 52.4 | Checkout | View on mobile | Responsive |
| 52.5 | Navigation | View on mobile | Responsive |

### 53. Tablet View
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 53.1 | Homepage | View on tablet | Responsive |
| 53.2 | Product detail | View on tablet | Responsive |
| 53.3 | Cart | View on tablet | Responsive |

---

## ⚡ Performance Tests

### 54. Page Load
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 54.1 | Homepage load | Measure load time | < 3 seconds |
| 54.2 | Product load | Measure load time | < 2 seconds |
| 54.3 | Search load | Measure load time | < 2 seconds |
| 54.4 | Image load | Measure load time | < 1 second |

### 55. Caching
| # | Feature | Test Case | Expected Result |
|---|---------|-----------|-----------------|
| 55.1 | Page cache | Check cache | Cache works |
| 55.2 | Image cache | Check cache | Cache works |
| 55.3 | API cache | Check cache | Cache works |

---

## 📊 Test Summary

| Category | Tests | Priority |
|----------|-------|----------|
| Frontend Pages | 100+ | 🔴 High |
| Authentication | 20+ | 🔴 High |
| Shopping Cart | 30+ | 🔴 High |
| User Profile | 10+ | 🟡 Medium |
| Admin Panel | 150+ | 🔴 High |
| API Endpoints | 30+ | 🟡 Medium |
| Components | 30+ | 🟡 Medium |
| Security | 15+ | 🔴 High |
| Responsive | 10+ | 🟡 Medium |
| Performance | 10+ | 🟢 Low |
| **Total** | **400+** | |

---

## 🚀 Execution Plan

### Phase 1: Critical Path (Day 1)
- Homepage, Product Detail, Search
- Login, Registration
- Cart, Checkout

### Phase 2: Admin Features (Day 2)
- Product Management
- Order Management
- Image Management

### Phase 3: Other Features (Day 3)
- User Profile
- Blog/News
- Reports

### Phase 4: Non-functional (Day 4)
- Security Tests
- Performance Tests
- Responsive Tests

---

**Total: 400+ test cases across 55 feature areas**
