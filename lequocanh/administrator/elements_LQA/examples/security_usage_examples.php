<?php
/**
 * Security Usage Examples - How to use the new security features
 * This file demonstrates how to implement CSRF protection and input validation
 */

// Include security middleware
require_once __DIR__ . '/../mod/securityMiddleware.php';

// Initialize security
SecurityMiddleware::init();

/**
 * Example 1: Form with CSRF Protection
 */
function exampleFormWithCSRF() {
    ?>
    <form method="POST" action="process_form.php">
        <?php echo SecurityMiddleware::getCsrfField(); ?>
        
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit">Submit</button>
    </form>
    
    <?php echo SecurityMiddleware::getSecurityScript(); ?>
    <?php
}

/**
 * Example 2: Processing Form with Validation
 */
function exampleProcessForm() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Define validation rules
        $rules = [
            'username' => 'required|min_length:3|max_length:50|alpha_numeric',
            'email' => 'required|email',
            'password' => 'required|min_length:8',
            'phone' => 'phone',
            'age' => 'integer|min_value:18|max_value:120'
        ];
        
        // Validate request with CSRF protection
        $validation = SecurityMiddleware::validatePostRequest($_POST, $rules, true);
        
        if ($validation['valid']) {
            // Process valid data
            $sanitizedData = $validation['sanitized_data'];
            
            // Your business logic here
            echo "Form processed successfully!";
            
            if (class_exists('Logger')) {
                Logger::info("Form processed", ['user' => $sanitizedData['username']]);
            }
        } else {
            // Handle validation errors
            foreach ($validation['errors'] as $field => $errors) {
                foreach ($errors as $error) {
                    echo "<div class='error'>$error</div>";
                }
            }
        }
    }
}

/**
 * Example 3: File Upload with Security
 */
function exampleFileUpload() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
        // Validate CSRF first
        if (!CSRFProtection::validateRequest($_POST)) {
            die("CSRF validation failed");
        }
        
        // Validate file upload
        $fileValidation = SecurityMiddleware::validateFileUpload($_FILES['upload'], [
            'max_size' => 2 * 1024 * 1024, // 2MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
        ]);
        
        if ($fileValidation['valid']) {
            $fileInfo = $fileValidation['file_info'];
            
            // Generate safe filename
            $safeFilename = uniqid() . '.' . $fileInfo['extension'];
            $uploadPath = '/uploads/' . $safeFilename;
            
            if (move_uploaded_file($fileInfo['tmp_name'], $uploadPath)) {
                echo "File uploaded successfully: " . $safeFilename;
                
                if (class_exists('Logger')) {
                    Logger::info("File uploaded", [
                        'filename' => $safeFilename,
                        'original_name' => $fileInfo['original_name'],
                        'size' => $fileInfo['size']
                    ]);
                }
            } else {
                echo "Failed to move uploaded file";
            }
        } else {
            foreach ($fileValidation['errors'] as $error) {
                echo "<div class='error'>$error</div>";
            }
        }
    }
    
    ?>
    <form method="POST" enctype="multipart/form-data">
        <?php echo SecurityMiddleware::getCsrfField(); ?>
        <input type="file" name="upload" accept="image/*" required>
        <button type="submit">Upload</button>
    </form>
    <?php
}

/**
 * Example 4: AJAX Request with CSRF
 */
function exampleAjaxWithCSRF() {
    ?>
    <script>
    // Get CSRF token
    var csrfToken = '<?php echo SecurityMiddleware::getCsrfToken(); ?>';
    
    // AJAX request with CSRF protection
    function makeSecureAjaxRequest() {
        $.ajax({
            url: 'ajax_endpoint.php',
            method: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken
            },
            data: {
                action: 'update_data',
                value: 'some_value'
            },
            success: function(response) {
                console.log('Success:', response);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
    </script>
    <?php
}

/**
 * Example 5: Authentication Check
 */
function exampleAuthCheck() {
    // Check if user is authenticated
    if (!SecurityMiddleware::checkAuth()) {
        // Redirect to login
        header('Location: /login.php');
        exit();
    }
    
    // Check if user has admin role
    if (!SecurityMiddleware::checkAuth(['admin', 'manager'])) {
        // Access denied
        http_response_code(403);
        echo "Access denied";
        exit();
    }
    
    echo "Welcome, admin!";
}

/**
 * Example 6: Rate Limiting
 */
function exampleRateLimit() {
    $userIP = $_SERVER['REMOTE_ADDR'];
    
    // Check rate limit for login attempts
    if (!SecurityMiddleware::checkRateLimit("login_$userIP", 5, 300)) {
        http_response_code(429);
        echo "Too many login attempts. Please try again later.";
        exit();
    }
    
    // Process login
    echo "Login form";
}

/**
 * Example 7: Input Sanitization
 */
function exampleInputSanitization() {
    $rawData = [
        'name' => '<script>alert("xss")</script>John Doe',
        'email' => 'john@example.com',
        'age' => '25abc',
        'website' => 'http://example.com'
    ];
    
    $sanitizationRules = [
        'name' => 'html',
        'email' => 'email',
        'age' => 'integer',
        'website' => 'url'
    ];
    
    $sanitizedData = InputValidator::sanitize($rawData, $sanitizationRules);
    
    print_r($sanitizedData);
    // Output:
    // [
    //     'name' => '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;John Doe',
    //     'email' => 'john@example.com',
    //     'age' => '25',
    //     'website' => 'http://example.com'
    // ]
}

/**
 * Example 8: Custom Validation Rules
 */
function exampleCustomValidation() {
    $data = [
        'username' => 'john_doe',
        'password' => 'mypassword123',
        'confirm_password' => 'mypassword123',
        'terms' => '1'
    ];
    
    $rules = [
        'username' => 'required|min_length:3|max_length:20|regex:/^[a-zA-Z0-9_]+$/',
        'password' => 'required|min_length:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
        'terms' => 'required|in:1'
    ];
    
    $validation = InputValidator::quickValidate($data, $rules);
    
    if ($validation['valid']) {
        echo "Validation passed!";
    } else {
        echo "Validation failed: " . $validation['first_error'];
    }
}

// Usage in your actual files:
/*
// At the top of your PHP files:
require_once __DIR__ . '/../mod/securityMiddleware.php';
SecurityMiddleware::init();

// In your forms:
echo SecurityMiddleware::getCsrfField();

// When processing forms:
$validation = SecurityMiddleware::validatePostRequest($_POST, $rules);
if ($validation['valid']) {
    // Process data
} else {
    // Handle errors
}

// For authentication:
if (!SecurityMiddleware::checkAuth(['admin'])) {
    // Redirect or show error
}
*/