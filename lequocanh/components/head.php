<head>
    <meta charset="UTF-8">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/lequocanh/manifest.json">
    <meta name="theme-color" content="#3498db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="LQA Shop">
    <link rel="apple-touch-icon" href="/lequocanh/public_files/images/icon-192.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/x-icon" href="/lequocanh/administrator/elements_LQA/img_LQA/no-image.png">
    <?= csrf_meta() ?>
    <base href="/lequocanh/">
    
    <!-- Preconnect hints for faster DNS/TLS -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">

    <title>Cửa Hàng Điện Thoại - Giá tốt nhất thị trường</title>
    <meta name="description" content="Cửa hàng điện thoại uy tín, chất lượng cao với giá tốt nhất. Giao hàng nhanh, bảo hành chính hãng.">

    <?php echo perf_head(); ?>

    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="/lequocanh/public_files/mycss.css" as="style">
    <link rel="preload" href="/lequocanh/public_files/bundle.min.css" as="style">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/lequocanh/public_files/mycss.css">
    <link rel="stylesheet" href="/lequocanh/public_files/bundle.min.css">

    <style>
        .navbar { z-index: 1030 !important; }
        .navbar.bg-dark { z-index: 1020 !important; position: relative; }
        .dropdown-menu { z-index: 1080 !important; position: absolute !important; background-color: white !important; border: 1px solid rgba(0, 0, 0, 0.15) !important; border-radius: 0.5rem !important; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important; }
        .navbar-nav .dropdown .dropdown-menu { position: absolute !important; z-index: 1080 !important; top: 100% !important; left: auto !important; right: 0 !important; transform: none !important; }
        #userDropdown+.dropdown-menu { z-index: 1090 !important; }
        .loading { opacity: 0.7; pointer-events: none; }
        .page-loader { position: fixed; top: 0; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #007bff, #28a745); z-index: 9999; transform: translateX(-100%); animation: loading 2s infinite; }
        @keyframes loading { 0% { transform: translateX(-100%); } 50% { transform: translateX(0); } 100% { transform: translateX(100%); } }
        .news-carousel-caption { background: rgba(0, 0, 0, 0.7); border-radius: 8px; padding: 15px; margin: 20px; }
        .news-carousel-caption h5 { font-size: 1.5rem; margin-bottom: 10px; }
        .news-carousel-caption p { font-size: 1rem; margin-bottom: 10px; }
        .news-carousel-caption small { font-size: 0.85rem; opacity: 0.9; }
        .pulse-animation { animation: pulse 2s infinite; box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); font-weight: 600; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); } 50% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); } }
        .pulse-animation:hover { animation: none; transform: scale(1.05); transition: transform 0.2s; }
        .hover-white:hover { color: #fff !important; transition: color 0.2s; }
        .blog-section { background: #f8f9fa; padding: 40px 0; margin-top: 30px; }
        .blog-section h3 { color: #333; margin-bottom: 25px; }
        .blog-card-home { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s; height: 100%; }
        .blog-card-home:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .blog-card-home img { width: 100%; height: 150px; object-fit: cover; }
        .blog-card-home .card-body { padding: 15px; }
        .blog-card-home .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .blog-card-home .card-title a { color: #333; text-decoration: none; }
        .blog-card-home .card-title a:hover { color: #0d6efd; }
        .blog-card-home .card-text { font-size: 0.85rem; color: #666; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        
        /* Lazy load placeholder */
        img[loading="lazy"] {
            background: linear-gradient(135deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            min-height: 100px;
        }
        img[loading="lazy"].loaded {
            background: none;
            animation: none;
        }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>


    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/lequocanh/sw.js')
                    .then(reg => console.log('SW registered:', reg.scope))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }
    </script>
</head>
