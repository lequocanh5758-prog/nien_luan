<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?= csrf_meta() ?>
    <base href="/lequocanh/">

    <title>Cửa Hàng Điện Thoại - Giá tốt nhất thị trường</title>
    <meta name="description" content="Cửa hàng điện thoại uy tín, chất lượng cao với giá tốt nhất. Giao hàng nhanh, bảo hành chính hãng.">

    <?php echo perf_head(); ?>

    <!-- Preload critical resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="public_files/mycss.css" as="style">

    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public_files/mycss.css">
    <link rel="stylesheet" href="public_files/notification.css">
    <link rel="stylesheet" href="public_files/product_filter.css">
    <link rel="stylesheet" href="public_files/product_reviews.css">
    <link rel="stylesheet" href="public_files/wishlist.css">

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
    </style>

</head>
