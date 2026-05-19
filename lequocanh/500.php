<?php
/**
 * Trang lỗi 500 - Lỗi server
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Lỗi máy chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5365c 0%, #f56036 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 40px;
        }
        .error-code {
            font-size: 150px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .error-description {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        .btn-home {
            background: white;
            color: #f5365c;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            margin: 5px;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: #f56036;
        }
        .btn-retry {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
        }
        .btn-retry:hover {
            background: white;
            color: #f5365c;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-code">500</div>
        <div class="error-message">Lỗi máy chủ</div>
        <div class="error-description">
            Đã xảy ra lỗi không mong muốn. Vui lòng thử lại sau.
        </div>
        <div>
            <a href="javascript:location.reload()" class="btn-home btn-retry">
                <i class="fas fa-redo me-2"></i>Thử lại
            </a>
            <a href="/lequocanh/index.php" class="btn-home">
                <i class="fas fa-home me-2"></i>Về trang chủ
            </a>
        </div>
    </div>
</body>
</html>