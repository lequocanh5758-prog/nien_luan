# CODEBASE.md - LeQuocAnh Shop E-Commerce System

**Analysis Date:** 2026-03-30
**Project:** `lequocanh/shop` - Vietnamese Phone/Electronics E-Commerce Platform
**Language:** Vietnamese (primary UI) + English (code)

---

## 1. Project Overview

**LeQuocAnh Shop** is a custom-built PHP e-commerce application for selling mobile phones and electronics in Vietnam. It is **NOT** a Laravel project despite the AGENTS.md reference — it is a bespoke PHP application with its own routing, ORM, configuration, and MVC-like architecture.

**Core Features:**
- Product catalog with categories, brands, promotions, and product reviews
- Shopping cart with session-based persistence
- Checkout with multiple payment methods (MoMo e-wallet, bank transfer, COD)
- Shipping integration with GHN (Giao Hàng Nhanh) delivery service
- Admin dashboard for product/order/user/coupon management
- Customer support ticket system
- Blog/news system with banners
- Wishlist functionality
- Order tracking and notifications
- Email notification system (PHPMailer)
- PDF invoice generation (TCPDF)
- Excel export (PhpSpreadsheet)
- SEO optimization helpers
- Performance monitoring and caching layers

**Domain:** `Cửa Hàng Điện Thoại` (Mobile Phone Shop)

---

## 2. Directory Structure

```
D:\PHP_WS/                          # Project root
├── index.php                       # Main entry point → delegates to router.php
├── router.php                      # URL routing engine (maps URIs to PHP files)
├── bootstrap.php                   # Application bootstrap (config, autoloader, security)
├── security.php                    # Security class (CSRF, rate limiting, input sanitization)
├── performance.php                 # Performance class (compression, caching headers)
├── composer.json                   # PHP dependencies (TCPDF, PhpSpreadsheet, PHPMailer)
├── .env / .env.example             # Environment configuration
├── .htaccess                       # Apache file protection rules
├── nginx.conf                      # Nginx reverse proxy config
├── docker-compose.yml              # Docker multi-service orchestration
├── Dockerfile                      # PHP web container
├── AGENTS.md                       # Agent coding guidelines
│
├── lequocanh/                      # ★ MAIN APPLICATION CODE
│   ├── index.php                   # Frontend homepage (899 lines - product listing, nav, footer)
│   ├── search.php                  # Product search
│   ├── blog.php                    # Blog listing
│   ├── news_detail.php             # News article detail
│   ├── page.php                    # Static pages (about, policies)
│   ├── track_order.php             # Order tracking
│   ├── sw.js                       # Service Worker (offline caching)
│   │
│   ├── app/                        # ★ MVC Layer (PSR-4: App\ namespace)
│   │   ├── autoload.php            # Custom autoloader
│   │   ├── Controllers/
│   │   │   ├── BaseController.php  # Abstract base controller (view, json, redirect, auth)
│   │   │   └── Admin/
│   │   │       └── ProductController.php
│   │   ├── Models/
│   │   │   ├── BaseModel.php       # Custom ORM (find, where, save, delete, create)
│   │   │   └── Product.php         # Product model (hanghoa table)
│   │   └── Services/
│   │       ├── CategoryService.php
│   │       ├── OrderService.php    # Order CRUD with query caching
│   │       ├── ProductService.php
│   │       ├── ShippingService.php
│   │       └── UserService.php
│   │
│   ├── administrator/              # ★ ADMIN PANEL
│   │   ├── index.php               # Admin dashboard (login-gated)
│   │   ├── userLogin.php           # Admin/user login page
│   │   ├── signUp.php              # User registration
│   │   ├── forgot_password.php     # Password recovery
│   │   ├── css_LQA/                # Admin CSS
│   │   ├── js_LQA/                 # Admin JavaScript
│   │   ├── stylecss_LQA/           # Admin styles
│   │   ├── layoutcss/              # Admin layout CSS
│   │   ├── uploads/                # Admin file uploads
│   │   └── elements_LQA/           # ★ CORE BUSINESS LOGIC MODULES
│   │       ├── mod/                # ~90 class files (the heart of the application)
│   │       │   ├── database.php              # Database singleton (PDO, multi-host fallback)
│   │       │   ├── database_debug.log        # DB connection debugging
│   │       │   ├── sessionManager.php        # Secure session management
│   │       │   ├── giohangCls.php            # Shopping cart class
│   │       │   ├── hanghoaCls.php            # Product class (~1956 lines, main business logic)
│   │       │   ├── loaihangCls.php           # Category class
│   │       │   ├── userCls.php               # User class
│   │       │   ├── khachhangCls.php          # Customer class
│   │       │   ├── nhanvienCls.php           # Employee class
│   │       │   ├── phanquyenCls.php          # Permission/authorization class
│   │       │   ├── thuonghieuCls.php         # Brand class
│   │       │   ├── donvitinhCls.php          # Unit of measure class
│   │       │   ├── dongiaCls.php             # Price class
│   │       │   ├── hinhanhCls.php            # Image class
│   │       │   ├── mtonkhoCls.php            # Inventory class
│   │       │   ├── mphieunhapCls.php         # Import receipt class
│   │       │   ├── nhacungcapCls.php         # Supplier class
│   │       │   ├── CouponCls.php             # Coupon/discount class
│   │       │   ├── ProductReviewCls.php      # Product review system
│   │       │   ├── ShippingCls.php           # Shipping management
│   │       │   ├── ShippingFeeModel.php      # Shipping fee calculation
│   │       │   ├── ShippingMethodCls.php      # Shipping methods
│   │       │   ├── GHNService.php            # GHN delivery API integration
│   │       │   ├── GHNMockService.php        # GHN mock for dev/testing
│   │       │   ├── MoMoPayment.php (in payment/) # MoMo payment gateway
│   │       │   ├── EmailService.php          # SMTP email via PHPMailer
│   │       │   ├── EmailNotificationCls.php  # Email notification triggers
│   │       │   ├── PromotionManager.php      # Promotional pricing
│   │       │   ├── BannerManager.php         # Banner management
│   │       │   ├── NewsManager.php           # News/blog management
│   │       │   ├── PageManager.php           # Static page management
│   │       │   ├── CacheService.php          # Cache abstraction
│   │       │   ├── queryCache.php            # Query result caching
│   │       │   ├── securityMiddleware.php     # Security middleware
│   │       │   ├── csrfProtection.php         # CSRF protection
│   │       │   ├── PasswordHelper.php        # Bcrypt password hashing
│   │       │   ├── TokenAuth.php             # Token-based auth
│   │       │   ├── ProvinceModel.php         # Vietnamese provinces
│   │       │   ├── DistrictModel.php         # Vietnamese districts
│   │       │   ├── WardModel.php             # Vietnamese wards
│   │       │   └── ... (90 total files)
│   │       ├── config/             # Logger and module configs
│   │       ├── mhanghoa/           # Product management views/actions
│   │       ├── mLoaihang/          # Category management
│   │       ├── mgiohang/           # Cart & checkout (49 files - heaviest module)
│   │       │   ├── checkout.php    # Checkout flow (~1480 lines)
│   │       │   ├── giohangView.php # Cart view
│   │       │   ├── giohangAct.php  # Cart actions (add/update/remove)
│   │       │   ├── momo_payment.php # MoMo payment initiation
│   │       │   ├── momo_return.php  # MoMo return handler
│   │       │   ├── payment_confirm.php # Payment confirmation
│   │       │   ├── shipping_method_selector*.php # Shipping selection UI
│   │       │   └── export/         # Order export functionality
│   │       ├── mUser/              # User management (13 files)
│   │       ├── mkhachhang/         # Customer management
│   │       ├── mnhanvien/          # Employee management
│   │       ├── mphanquyen/         # Permission management
│   │       ├── mcoupon/            # Coupon management
│   │       ├── mhinhanh/           # Image management
│   │       ├── mthongbao/          # Notification management
│   │       ├── msupport_tickets/   # Support ticket management
│   │       ├── mreview_management/ # Review management
│   │       ├── msanphamnoibat/     # Featured products
│   │       ├── mbaocao/            # Reports
│   │       ├── mthuonghieu/        # Brand management
│   │       ├── mdongia/            # Price management
│   │       ├── mdonvitinh/         # Unit management
│   │       ├── mmphieunhap/        # Import receipt management
│   │       ├── mmtonkho/           # Inventory management
│   │       ├── mnhacungcap/        # Supplier management
│   │       ├── mthuoctinh/         # Attribute management
│   │       ├── monitoring/         # System monitoring
│   │       └── security/           # Security utilities
│   │
│   ├── api/                        # ★ REST API ENDPOINTS
│   │   ├── Response.php            # JSON response helper
│   │   ├── cart.php                # Cart API
│   │   ├── wishlist.php            # Wishlist API
│   │   ├── filter_products.php     # Product filtering API
│   │   ├── product_reviews.php     # Reviews API
│   │   ├── submit_review.php       # Submit review
│   │   ├── user_addresses.php      # User addresses API
│   │   ├── support_tickets.php     # Support tickets API
│   │   ├── clear_cache.php         # Cache management API
│   │   ├── middleware/
│   │   │   ├── ApiSecurityMiddleware.php
│   │   │   ├── JwtAuthMiddleware.php   # JWT authentication (Firebase\JWT)
│   │   │   └── RateLimitMiddleware.php
│   │   ├── v1/
│   │   │   ├── index.php           # API v1 router
│   │   │   └── endpoints/
│   │   │       └── products.php    # Product API endpoint
│   │   └── v2/                     # API v2 (future)
│   │
│   ├── payment/                    # ★ PAYMENT INTEGRATION
│   │   ├── MoMoConfig.php         # MoMo configuration
│   │   ├── MoMoPayment.php        # MoMo payment class (HMAC-SHA256 signing)
│   │   ├── momo_process.php       # MoMo payment processing
│   │   ├── notify.php             # Payment notification handler
│   │   ├── return.php             # Payment return handler
│   │   ├── bank_notify.php        # Bank transfer notification
│   │   └── transactions.php       # Transaction management
│   │
│   ├── customer/                   # CUSTOMER SELF-SERVICE
│   │   ├── order_history.php       # Order history
│   │   ├── order_invoice.php       # PDF invoice
│   │   └── support.php             # Support ticket submission
│   │
│   ├── components/                 # REUSABLE UI COMPONENTS
│   │   ├── featuredProductsDisplay.php
│   │   ├── product_review_display.php
│   │   ├── product_review_widget.php
│   │   └── productStatusDisplay.php
│   │
│   ├── apart/                      # VIEW PARTIALS
│   │   ├── menuLoaihang.php        # Category navigation menu
│   │   ├── viewListLoaihang.php    # Product listing by category
│   │   ├── viewHangHoa.php         # Product detail view
│   │   ├── featuredProducts.php    # Featured products section
│   │   └── news_section.php        # News/promotions section
│   │
│   ├── config/                     # ★ CONFIGURATION
│   │   ├── ConfigManager.php       # Central config manager (singleton, .env parser)
│   │   ├── app.php                 # App configuration
│   │   ├── database.php            # Database connections config
│   │   ├── payment_config.php      # Payment gateway config
│   │   ├── logging.php             # Logging configuration
│   │   ├── performance.php         # Performance settings
│   │   └── local_config.php        # Local overrides
│   │
│   ├── includes/                   # SHARED UTILITIES
│   │   ├── csrf_helper.php         # CSRF token helpers
│   │   ├── query_builder.php       # Query builder utility
│   │   ├── advanced_cache.php      # Advanced caching
│   │   ├── page_cache.php          # Full-page caching
│   │   ├── performance_bootstrap.php # Performance initialization
│   │   ├── session_security.php    # Session security
│   │   ├── upload_security.php     # File upload security
│   │   ├── seo_helper.php          # SEO meta helpers
│   │   ├── html_optimizer.php      # HTML minification
│   │   ├── image_optimizer.php     # Image optimization
│   │   ├── asset_minifier.php      # CSS/JS minification
│   │   └── async_loader.php        # Async resource loading
│   │
│   ├── database/                   # DATABASE SCRIPTS
│   │   ├── create_tables.php       # Schema creation (provinces, districts, wards)
│   │   ├── trainingdb_backup.sql   # Full database backup (~1476 lines)
│   │   ├── create_product_reviews.sql
│   │   ├── create_coupon_tables.sql
│   │   ├── create_shipping_tables.sql
│   │   ├── create_banner_news_promotion_tables.sql
│   │   └── ... (60 SQL/PHP migration files)
│   │
│   ├── cache/                      # CACHE STORAGE
│   │   ├── QueryCache.php          # Query result cache
│   │   ├── CacheManager.php        # Cache manager
│   │   ├── PageCache.php           # Page cache
│   │   ├── pages/                  # Cached page files
│   │   └── images/                 # Cached images
│   │
│   ├── public_files/               # FRONTEND ASSETS
│   │   ├── mycss.css               # Main stylesheet
│   │   ├── critical.css            # Critical CSS (above-fold)
│   │   ├── search.js               # Search autocomplete
│   │   ├── product_filter.js       # Product filtering
│   │   ├── product_reviews.js      # Review interactions
│   │   ├── wishlist.js             # Wishlist functionality
│   │   ├── notification.js         # Notification system
│   │   ├── performance.js          # Performance monitoring
│   │   └── js/csrf-helper.js       # CSRF AJAX helper
│   │
│   ├── uploads/                    # USER UPLOADS
│   ├── logs/                       # APPLICATION LOGS
│   └── cron/                       # CRON JOB SCRIPTS
│
├── monitoring/                     # MONITORING CONFIG
│   └── prometheus.yml              # Prometheus scrape config
│
├── vendor/                         # COMPOSER DEPENDENCIES
├── logs/                           # ROOT-LEVEL LOGS
├── DB/                             # EMPTY - database scripts in lequocanh/database/
└── test-results/                   # TEST OUTPUT
```

---

## 3. Tech Stack

### Languages
| Language | Version | Usage |
|----------|---------|-------|
| PHP | >= 7.4 | Server-side application logic |
| JavaScript | ES6+ | Frontend interactivity, AJAX |
| SQL | MySQL 8.0 | Database queries |
| HTML5 | - | Templates (embedded PHP) |
| CSS3 | - | Styling (Bootstrap 5 + custom) |

### Frameworks & Libraries
| Package | Version | Purpose |
|---------|---------|---------|
| Bootstrap | 5.3.3 (CDN) | Frontend CSS framework |
| jQuery | 3.6.0 / 3.7.1 (CDN) | DOM manipulation, AJAX |
| Font Awesome | 6.0.0 (CDN) | Icon library |
| tecnickcom/tcpdf | ^6.6 | PDF invoice generation |
| phpoffice/phpspreadsheet | ^1.29 | Excel export/import |
| phpmailer/phpmailer | ^7.0 | SMTP email sending |
| Firebase\JWT | (via autoload) | JWT token auth for API |

### Infrastructure
| Service | Purpose |
|---------|---------|
| MySQL 8.0 | Primary database (`trainingdb` / `sales_management`) |
| Redis 7 (Alpine) | Caching layer |
| Nginx (Alpine) | Reverse proxy with SSL |
| PHP-FPM | PHP runtime (Docker) |
| Prometheus | Metrics collection |
| Grafana | Monitoring dashboards |
| phpMyAdmin | Database admin UI |
| Cloudflare Tunnel / ngrok | External tunneling for dev |

### Build & Dev Tools
| Tool | Purpose |
|------|---------|
| Composer | PHP dependency management |
| Docker / Docker Compose | Container orchestration |
| Git | Version control |
| GitNexus | Code intelligence indexing |

---

## 4. Architecture

### Pattern: Custom MVC with Module-Based Organization

This is a **hand-rolled PHP framework** — not based on any established framework. It implements its own:

- **Routing**: `router.php` maps URL paths to PHP files (both static routes and fallback file resolution)
- **ORM**: `BaseModel.php` provides ActiveRecord-style `find()`, `where()`, `save()`, `delete()`
- **Config**: `ConfigManager.php` singleton loads `.env` + PHP config arrays
- **DI**: Manual singleton pattern (`Database::getInstance()`, `ConfigManager::getInstance()`)
- **Auth**: Session-based with `$_SESSION['USER']` / `$_SESSION['ADMIN']` flags

### Layer Breakdown

```
┌─────────────────────────────────────────────────────┐
│                    Frontend (PHP+HTML+JS)            │
│  lequocanh/index.php, apart/, components/, public_files/ │
├─────────────────────────────────────────────────────┤
│                    Router Layer                       │
│  router.php → static routes + file fallback          │
├─────────────────────────────────────────────────────┤
│              Admin Modules (elements_LQA/mod/)        │
│  ~90 class files - core business logic               │
│  hanghoaCls, giohangCls, userCls, etc.               │
├─────────────────────────────────────────────────────┤
│                    API Layer                          │
│  api/v1/, api/middleware/ (JWT, rate limiting)        │
├─────────────────────────────────────────────────────┤
│                    Service Layer                      │
│  app/Services/ (OrderService, ProductService, etc.)  │
├─────────────────────────────────────────────────────┤
│                    Model Layer                        │
│  app/Models/ (BaseModel ORM + Product model)         │
│  + legacy class-based models in elements_LQA/mod/     │
├─────────────────────────────────────────────────────┤
│                    Database (PDO + MySQL)             │
│  Database singleton → PDO → MySQL 8.0                │
├─────────────────────────────────────────────────────┤
│              External Integrations                    │
│  MoMo Payment | GHN Shipping | SMTP Email            │
└─────────────────────────────────────────────────────┘
```

### Key Design Patterns

1. **Singleton**: `Database`, `ConfigManager`, `OrderService`, `QueryCache` all use singleton pattern
2. **Active Record**: `BaseModel` implements find/where/save/delete on database tables
3. **Front Controller**: `index.php` → `router.php` → target PHP file
4. **Template Partials**: `apart/` directory contains reusable view fragments (`require`'d into pages)
5. **Service Layer**: Newer `app/Services/` classes wrap database queries with caching
6. **Module Pattern**: Each admin feature area has its own directory under `elements_LQA/m*`

### Dual Codebase Reality

There are **two parallel implementations** of many features:

| Feature | Legacy (elements_LQA/mod/) | Modern (app/) |
|---------|---------------------------|---------------|
| Products | `hanghoaCls.php` (1956 lines) | `Models/Product.php`, `Services/ProductService.php` |
| Orders | `giohangCls.php` | `Services/OrderService.php` |
| Users | `userCls.php` | `Services/UserService.php` |
| Categories | `loaihangCls.php` | `Services/CategoryService.php` |

The **legacy code in `elements_LQA/mod/` is the active production code**. The `app/` layer is newer and partially adopted.

---

## 5. Key Entry Points

### Web Entry Points
| URL | File | Purpose |
|-----|------|---------|
| `/` | `index.php` → `router.php` → `lequocanh/index.php` | Homepage |
| `/admin` | `router.php` → `lequocanh/administrator/index.php` | Admin dashboard |
| `/admin/login` | `router.php` → `lequocanh/administrator/userLogin.php` | Login page |
| `/api/momo/callback` | `router.php` → `lequocanh/api/momo_callback.php` | MoMo payment callback |
| `/api/momo/ipn` | `router.php` → `lequocanh/api/momo_ipn.php` | MoMo IPN handler |

### Router Fallback
Any URL like `/lequocanh/<path>` resolves to `lequocanh/<path>` if the file exists. This means all PHP files under `lequocanh/` are directly accessible.

### Bootstrap Chain
```
index.php
  └→ router.php
       └→ bootstrap.php
            ├→ ConfigManager::getInstance() (loads .env + config files)
            ├→ security.php (Security::setSecureHeaders())
            ├→ performance.php (Performance class)
            └→ spl_autoload_register (PSR-4 + legacy paths)
                 ├→ SessionManager::start()
                 ├→ ErrorTracker::registerErrorHandler()
                 └→ RealtimePerformanceMonitor::startOperation()
```

---

## 6. Database

### Database Name
- Development: `trainingdb` / `sales_management`
- Docker: `sales_management` (user: `app_user` / `app_password`)

### Core Tables (from SQL backup analysis)
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `hanghoa` | Products | `idhanghoa`, `tenhanghoa`, `giathamkhao`, `hinhanh`, `idloaihang`, `idThuongHieu` |
| `loaihang` | Product categories | `idloaihang`, `tenloaihang` |
| `thuonghieu` | Brands | `idThuongHieu`, `tenTH` |
| `donvitinh` | Units of measure | `idDonViTinh`, `tenDonViTinh` |
| `tonkho` | Inventory | `idhanghoa`, `soLuong`, `soLuongToiThieu` |
| `user` | Users | `iduser`, `username`, `hoten`, `dienthoai`, `diachi` |
| `nhanvien` | Employees | `idNhanVien`, `iduser`, `tenNV` |
| `don_hang` | Orders | `id`, `ma_don_hang_text`, `tong_tien`, `trang_thai`, `ma_nguoi_dung` |
| `chi_tiet_don_hang` | Order details | `ma_don_hang`, `ma_san_pham`, `so_luong`, `gia` |
| `tbl_giohang` | Shopping cart | `user_id`, `product_id`, `quantity` |
| `phieunhap` | Import receipts | `idPhieuNhap`, ... |
| `chitietphieunhap` | Import details | `idCTPN`, `idPhieuNhap`, `idhanghoa`, `soLuong` |
| `nhacungcap` | Suppliers | ... |
| `banners` | Homepage banners | `id`, `title`, `image_url`, `is_active` |
| `product_reviews` | Product reviews | `idhanghoa`, `iduser`, `rating`, `review_text` |
| `provinces` | Vietnamese provinces | `code`, `name`, `region` |
| `districts` | Districts | `province_id`, `code`, `name` |
| `wards` | Wards | `district_id`, `code`, `name` |
| `shipping_methods` | Shipping methods | ... |
| `shipping_fees` | Shipping fee rules | ... |
| `coupons` | Discount coupons | ... |
| `cau_hinh_thanh_toan` | Bank payment config | `ten_ngan_hang`, `so_tai_khoan` |

### Data Access
- **Primary**: Raw PDO with prepared statements (via `Database::getInstance()->getConnection()`)
- **ORM**: `BaseModel` class provides ActiveRecord pattern (used by `Product` model)
- **Legacy**: Most business logic uses raw SQL in class methods (e.g., `hanghoaCls.php`)
- **Caching**: `QueryCache` wraps frequently-accessed queries with TTL-based caching

### Vietnamese Naming Convention
Database tables and columns use Vietnamese names:
- `hanghoa` = goods/products
- `loaihang` = product categories
- `thuonghieu` = brands
- `don_hang` = orders
- `chi_tiet_don_hang` = order details
- `giathamkhao` = reference price
- `tenhanghoa` = product name

---

## 7. Dependencies

### composer.json
```json
{
    "name": "lequocanh/shop",
    "type": "project",
    "require": {
        "php": ">=7.4",
        "tecnickcom/tcpdf": "^6.6",
        "phpoffice/phpspreadsheet": "^1.29",
        "phpmailer/phpmailer": "^7.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "lequocanh/app/"
        }
    }
}
```

### CDN Dependencies (loaded in HTML)
- Bootstrap 5.3.3 (CSS + JS bundle)
- jQuery 3.6.0 / 3.7.1
- Font Awesome 6.0.0
- Popper.js 2.11.8

### Implicit Dependencies (loaded via require/include)
- `Firebase\JWT` (JWT auth - referenced in `JwtAuthMiddleware.php` but may not be in composer.json)
- Custom autoloader in `bootstrap.php` searches 8 directories

---

## 8. Configuration

### Environment Variables (.env)
```
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost / mysql (Docker)
DB_PORT=3306
DB_DATABASE=sales_management
DB_USERNAME=root / app_user
DB_PASSWORD=...
JWT_SECRET=...
MOMO_PARTNER_CODE=...
MOMO_ACCESS_KEY=...
MOMO_SECRET_KEY=...
GHN_API_TOKEN=...
MAIL_HOST=smtp.gmail.com
MAIL_USERNAME=...
MAIL_PASSWORD=...
BASE_URL=...
```

### Config Files (lequocanh/config/)
| File | Purpose |
|------|---------|
| `ConfigManager.php` | Central config singleton, .env parser, dot-notation access |
| `app.php` | App name, environment, URL, debug mode |
| `database.php` | MySQL connection configs (primary + fallback hosts) |
| `payment_config.php` | MoMo, bank transfer, COD payment settings |
| `logging.php` | Log channels, rotation, levels |
| `performance.php` | Cache TTL, query optimization, slow query thresholds |
| `local_config.php` | Local development overrides |

### Database Connection Strategy
The `Database` class tries **10 different credential/host combinations** on connection failure — a resilience pattern for Docker vs local development environments.

---

## 9. Testing

### Test Framework
**No formal test framework is configured.** There is no PHPUnit, Codeception, or any test runner.

### Test Files
The project has ~21 `test_*.php` files scattered across the codebase. These are **manual ad-hoc test scripts**, not automated tests:

| File | Purpose |
|------|---------|
| `test_cart.php` | Manual cart testing |
| `test_featured.php` | Featured products display test |
| `test_save_to_db.php` | Database save test |
| `test_display_direct.php` | Display rendering test |
| `lequocanh/test_email_direct.php` | Email sending test |
| `lequocanh/database/test_connection.php` | DB connection test |
| `lequocanh/database/test_calc_shipping_fee.php` | Shipping fee calculation test |
| `lequocanh/administrator/test_employee_permission.php` | Permission test |
| `lequocanh/administrator/elements_LQA/mod/test_shipping_calc.php` | Shipping calculation test |

### AGENTS.md Testing Commands
The AGENTS.md references `composer test-coverage` and `phpunit`, but **no phpunit.xml or phpunit configuration exists** in the project.

---

## 10. Key Workflows

### Request Lifecycle
```
Browser Request
  → .htaccess / Nginx → index.php
    → router.php (security checks, rate limiting)
      → bootstrap.php (config, autoloader, security headers)
        → Target PHP file (view logic + business logic)
          → Database singleton → PDO → MySQL
            → HTML response with embedded PHP
```

### Authentication Flow
1. **Login**: `administrator/userLogin.php` → POST to `elements_LQA/mUser/userAct.php?reqact=userlogin`
2. **Session**: `$_SESSION['USER']` for customers, `$_SESSION['ADMIN']` for admins
3. **Session Security**: `SessionManager` + `SessionSecurity` (timeout, validation, regeneration)
4. **Password**: Bcrypt hashing via `PasswordHelper.php`
5. **Admin Access**: Employee check via `nhanvien` table join
6. **Permissions**: Role-based via `phanquyenCls.php` (module-level access control)

### Cart & Checkout Flow
1. **Add to Cart**: AJAX → `giohangCls::addToCart()` → `tbl_giohang` table
2. **Cart View**: `mgiohang/giohangView.php` → displays cart items with quantity controls
3. **Checkout**: `mgiohang/checkout.php` → address selection → shipping method selection → payment method
4. **Payment**: 
   - **MoMo**: `momo_payment.php` → MoMo API redirect → `momo_return.php` callback
   - **Bank Transfer**: Display bank details → manual confirmation
   - **COD**: Direct order creation
5. **Order Creation**: `giohangAct.php` → `don_hang` + `chi_tiet_don_hang` tables
6. **Notifications**: `EmailNotificationCls` sends order confirmation email

### Payment Integration (MoMo)
```
checkout.php
  → init_payment.php
    → MoMoPayment::createPayment()
      → HMAC-SHA256 signature generation
      → POST to MoMo API (test-payment.momo.vn)
      → Redirect user to MoMo payment page
        → User completes payment
          → MoMo redirects to return.php
          → MoMo sends IPN to notify.php
            → Verify signature
            → Update order status
            → Send confirmation email
```

### Shipping Integration (GHN)
```
checkout.php
  → get_shipping_methods.php
    → GHNService::getAvailableServices()
      → [If API token configured]: GHN API call
      → [If no token]: GHNMockService returns mock data
    → Returns available shipping methods + fees
  → User selects method
  → Order stores shipping_method_id + phi_van_chuyen
```

### Product Management (Admin)
```
Admin Dashboard → elements_LQA/mhanghoa/
  → hanghoaView.php (product list with CRUD)
  → hanghoaUpdate.php (product form)
  → hanghoaAct.php (save/delete actions)
  → displayImage.php (product image serving)
```

---

## 11. Potential Concerns

### Security Issues

1. **Hardcoded Credentials in Source Code**
   - `database.php` line 33-41: Contains 10 hardcoded username/password combinations
   - `payment_config.php`: MoMo secret keys visible in source as defaults
   - Risk: Credentials exposed if source code leaks

2. **SSL Verification Disabled**
   - `MoMoPayment.php` line 49: `CURLOPT_SSL_VERIFYPEER = false`
   - Risk: Man-in-the-middle attacks on payment API calls

3. **CSP Allows unsafe-inline and unsafe-eval**
   - `security.php` line 25: `script-src 'self' 'unsafe-inline' 'unsafe-eval'`
   - Risk: XSS attacks bypass CSP protection

4. **Fallback JWT Secret**
   - `JwtAuthMiddleware.php` line 17: Generates fallback secret from file path
   - Risk: Predictable secret if JWT_SECRET env var is not set

5. **SQL Injection Risk in BaseModel**
   - `BaseModel.php` line 95: `where()` method interpolates `$column` and `$operator` directly into SQL
   - `$column` and `$operator` are NOT parameterized — only `$value` is
   - Risk: SQL injection if user-controlled values reach `where()` column/operator params

6. **Direct File Access**
   - Router fallback allows any `.php` file under `lequocanh/` to be accessed directly
   - `test_*.php` files are web-accessible

### Architecture Concerns

7. **Massive Legacy Files**
   - `hanghoaCls.php`: ~1,956 lines (product class with queries, filtering, sorting)
   - `checkout.php`: ~1,480 lines (mixed HTML, business logic, payment, shipping)
   - `giohangCls.php`: ~361 lines with complex cart logic
   - These files mix database queries, business logic, and presentation

8. **Dual Codebase (Legacy vs Modern)**
   - `app/Models/` and `app/Services/` exist but most business logic still lives in `elements_LQA/mod/`
   - `BaseModel` ORM is only used by `Product` model — everything else uses raw SQL
   - Creates confusion about where new code should go

9. **No Test Coverage**
   - Zero automated tests
   - Ad-hoc `test_*.php` scripts are manual browser-based tests
   - No CI/CD pipeline configured

10. **Backup Files in Source**
    - Multiple `.backup` and `.backup_*` files committed:
      - `hanghoaCls.php.backup.20251205083507`
      - `giohangAct.php.backup_20260104_103610`
      - `userAct.php.backup_20260104_103610`
      - `phanquyenCls.php.backup.20250524190315`
    - These should be in git history, not as separate files

11. **Database Connection Fallback Brute Force**
    - `database.php` tries 10 different credential combinations on every failed connection
    - This masks configuration problems and creates noisy error logs

12. **Session-Based Rate Limiting**
    - `security.php` rate limiting uses `$_SESSION` — ineffective against distributed attacks
    - Rate limits reset when session expires

13. **Mixed Language in Code**
    - Vietnamese comments and variable names mixed with English class/method names
    - Database columns are Vietnamese (`tenhanghoa`, `giathamkhao`)
    - Code comments switch between Vietnamese and English

### Performance Concerns

14. **N+1 Query Risk**
    - Product listing pages load categories, brands, stock info per product
    - `Product::getCategory()`, `getBrand()`, `getStock()` each make separate DB queries
    - No eager loading mechanism

15. **No Database Migrations**
    - Schema changes managed through scattered `.sql` files and `create_tables.php`
    - No version tracking for schema changes
    - `trainingdb_backup.sql` is the closest thing to a schema definition

16. **CDN Dependencies Without SRI**
    - Bootstrap, jQuery, Font Awesome loaded from CDN without Subresource Integrity
    - Risk: CDN compromise could inject malicious code

### Deployment Concerns

17. **Docker Port Mapping**
    - Ports mapped to very high numbers (20080, 23306, 26379, 29090) to avoid Windows conflicts
    - Indicates this is primarily a development setup, not production-hardened

18. **No Production Configuration**
    - No separate production `.env` or config
    - Debug mode enabled by default in development
    - `error_reporting(E_ALL)` in admin index.php

---

## Summary Statistics

| Metric | Value |
|--------|-------|
| Total PHP files | ~476 |
| Core business logic files (elements_LQA/mod/) | ~90 |
| Admin module directories | ~25 |
| API endpoints | ~20 |
| Database tables | ~25+ |
| Composer dependencies | 3 (TCPDF, PhpSpreadsheet, PHPMailer) |
| CDN dependencies | 4 (Bootstrap, jQuery, Font Awesome, Popper.js) |
| Test files | ~21 (all manual/ad-hoc) |
| Lines in largest file | ~1,956 (hanghoaCls.php) |
| Docker services | 7 (web, mysql, redis, nginx, prometheus, grafana, phpmyadmin) |

---

*Codebase analysis: 2026-03-30*
