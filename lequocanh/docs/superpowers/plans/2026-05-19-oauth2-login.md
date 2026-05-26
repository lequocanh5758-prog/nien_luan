# OAuth2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable social login (Google/Facebook) to increase user registration by 30-40%.

**Architecture:** OAuth2 flow with Google and Facebook as Identity Providers. Users can link multiple social accounts to one local account.

**Tech Stack:** Google OAuth 2.0, Facebook Login, PHP, MySQL

---

## File Structure

| File                                       | Responsibility      |
| ------------------------------------------ | ------------------- |
| `config/oauth.php`                         | OAuth configuration |
| `app/Services/OAuthService.php`            | OAuth logic         |
| `app/Controllers/AuthController.php`       | Auth endpoints      |
| `routes/auth.php`                          | Auth routes         |
| `resources/views/auth/login.php`           | Login page          |
| `database/migrations/add_oauth_fields.php` | DB schema           |

---

### Task 1: OAuth Configuration

**Files:**

- Create: `config/oauth.php`
- Modify: `.env`

- [ ] **Step 1: Create OAuth config**

```php
<?php
// config/oauth.php
return [
    'google' => [
        'enabled' => (bool)$_ENV['GOOGLE_OAUTH_ENABLED'] ?? false,
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? '/auth/google/callback',
        'scopes' => ['email', 'profile'],
    ],
    'facebook' => [
        'enabled' => (bool)$_ENV['FACEBOOK_OAUTH_ENABLED'] ?? false,
        'client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['FACEBOOK_REDIRECT_URI'] ?? '/auth/facebook/callback',
        'scopes' => ['email', 'public_profile'],
    ],
];
```

- [ ] **Step 2: Add OAuth variables to .env**

```bash
# .env
GOOGLE_OAUTH_ENABLED=true
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=https://lqashop.com/auth/google/callback

FACEBOOK_OAUTH_ENABLED=true
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_REDIRECT_URI=https://lqashop.com/auth/facebook/callback
```

- [ ] **Step 3: Commit**

```bash
git add config/oauth.php .env
git commit -m "feat: add OAuth2 configuration"
```

---

### Task 2: Database Migration

**Files:**

- Create: `database/migrations/2026_05_19_add_oauth_fields.php`

- [ ] **Step 1: Create migration**

```php
<?php
// database/migrations/2026_05_19_add_oauth_fields.php
class AddOAuthFields
{
    public function up()
    {
        $db = \Database::getInstance()->getConnection();

        // Add OAuth fields to users table
        $db->exec("
            ALTER TABLE users
            ADD COLUMN google_id VARCHAR(50) NULL AFTER email,
            ADD COLUMN facebook_id VARCHAR(50) NULL AFTER google_id,
            ADD COLUMN avatar_url VARCHAR(500) NULL AFTER facebook_id,
            ADD COLUMN auth_provider ENUM('local', 'google', 'facebook') DEFAULT 'local' AFTER avatar_url
        ");

        // Create indexes
        $db->exec("CREATE INDEX idx_users_google_id ON users(google_id)");
        $db->exec("CREATE INDEX idx_users_facebook_id ON users(facebook_id)");
        $db->exec("CREATE INDEX idx_users_auth_provider ON users(auth_provider)");
    }

    public function down()
    {
        $db = \Database::getInstance()->getConnection();

        $db->exec("ALTER TABLE users DROP COLUMN google_id");
        $db->exec("ALTER TABLE users DROP COLUMN facebook_id");
        $db->exec("ALTER TABLE users DROP COLUMN avatar_url");
        $db->exec("ALTER TABLE users DROP COLUMN auth_provider");
    }
}
```

- [ ] **Step 2: Run migration**

```bash
php database/migrate.php
```

- [ ] **Step 3: Verify migration**

```sql
DESCRIBE users;
-- Should show: google_id, facebook_id, avatar_url, auth_provider columns
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add OAuth fields to users table"
```

---

### Task 3: OAuth Service

**Files:**

- Create: `app/Services/OAuthService.php`
- Test: `tests/Unit/OAuthServiceTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
// tests/Unit/OAuthServiceTest.php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\OAuthService;

class OAuthServiceTest extends TestCase
{
    public function testGetGoogleAuthUrl()
    {
        $service = new OAuthService([
            'google' => [
                'client_id' => 'test_client_id',
                'redirect_uri' => 'https://example.com/callback',
                'scopes' => ['email', 'profile'],
            ]
        ]);

        $url = $service->getGoogleAuthUrl();
        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('test_client_id', $url);
    }

    public function testGetFacebookAuthUrl()
    {
        $service = new OAuthService([
            'facebook' => [
                'client_id' => 'test_fb_id',
                'redirect_uri' => 'https://example.com/callback',
                'scopes' => ['email'],
            ]
        ]);

        $url = $service->getFacebookAuthUrl();
        $this->assertStringContainsString('facebook.com', $url);
        $this->assertStringContainsString('test_fb_id', $url);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/phpunit tests/Unit/OAuthServiceTest.php -v
```

Expected: FAIL with "Class App\Services\OAuthService not found"

- [ ] **Step 3: Write implementation**

```php
<?php
declare(strict_types=1);

namespace App\Services;

class OAuthService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get Google OAuth URL
     */
    public function getGoogleAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => $this->config['google']['client_id'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $this->config['google']['scopes']),
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        return "https://accounts.google.com/o/oauth2/auth?{$params}";
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(string $code): array
    {
        // Exchange code for tokens
        $tokens = $this->exchangeGoogleCode($code);

        // Get user info
        $userInfo = $this->getGoogleUserInfo($tokens['access_token']);

        return [
            'provider' => 'google',
            'provider_id' => $userInfo['id'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'avatar' => $userInfo['picture'] ?? null,
        ];
    }

    private function exchangeGoogleCode(string $code): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code' => $code,
            'client_id' => $this->config['google']['client_id'],
            'client_secret' => $this->config['google']['client_secret'],
            'redirect_uri' => $this->config['google']['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getGoogleUserInfo(string $accessToken): array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Get Facebook OAuth URL
     */
    public function getFacebookAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => $this->config['facebook']['client_id'],
            'redirect_uri' => $this->config['facebook']['redirect_uri'],
            'scope' => implode(',', $this->config['facebook']['scopes']),
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/v18.0/dialog/oauth?{$params}";
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function handleFacebookCallback(string $code): array
    {
        // Exchange code for tokens
        $tokens = $this->exchangeFacebookCode($code);

        // Get user info
        $userInfo = $this->getFacebookUserInfo($tokens['access_token']);

        return [
            'provider' => 'facebook',
            'provider_id' => $userInfo['id'],
            'email' => $userInfo['email'] ?? null,
            'name' => $userInfo['name'],
            'avatar' => $userInfo['picture']['data']['url'] ?? null,
        ];
    }

    private function exchangeFacebookCode(string $code): array
    {
        $params = http_build_query([
            'code' => $code,
            'client_id' => $this->config['facebook']['client_id'],
            'client_secret' => $this->config['facebook']['client_secret'],
            'redirect_uri' => $this->config['facebook']['redirect_uri'],
        ]);

        $ch = curl_init("https://graph.facebook.com/v18.0/oauth/access_token?{$params}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function getFacebookUserInfo(string $accessToken): array
    {
        $ch = curl_init("https://graph.facebook.com/me?fields=id,name,email,picture&access_token={$accessToken}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Create from config
     */
    public static function fromConfig(): self
    {
        $config = require __DIR__ . '/../../config/oauth.php';
        return new self($config);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
./vendor/bin/phpunit tests/Unit/OAuthServiceTest.php -v
```

Expected: OK (2 tests, 4 assertions)

- [ ] **Step 5: Commit**

```bash
git add app/Services/OAuthService.php tests/Unit/OAuthServiceTest.php
git commit -m "feat: add OAuthService for Google/Facebook login"
```

---

### Task 4: Auth Controller

**Files:**

- Create: `app/Controllers/AuthController.php`
- Create: `routes/auth.php`

- [ ] **Step 1: Create AuthController**

```php
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\OAuthService;
use App\Models\User;

class AuthController
{
    private OAuthService $oauth;

    public function __construct()
    {
        $this->oauth = OAuthService::fromConfig();
    }

    /**
     * Redirect to Google OAuth
     */
    public function googleRedirect(): void
    {
        $url = $this->oauth->getGoogleAuthUrl();
        header("Location: {$url}");
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback(): void
    {
        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['error'] = 'Google login failed';
            header('Location: /login');
            exit;
        }

        try {
            $userInfo = $this->oauth->handleGoogleCallback($code);
            $user = $this->findOrCreateUser($userInfo);

            $_SESSION['USER'] = $user->username;
            $_SESSION['login_success'] = true;

            header('Location: /');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Google login failed: ' . $e->getMessage();
            header('Location: /login');
        }
    }

    /**
     * Redirect to Facebook OAuth
     */
    public function facebookRedirect(): void
    {
        $url = $this->oauth->getFacebookAuthUrl();
        header("Location: {$url}");
        exit;
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function facebookCallback(): void
    {
        $code = $_GET['code'] ?? '';

        if (empty($code)) {
            $_SESSION['error'] = 'Facebook login failed';
            header('Location: /login');
            exit;
        }

        try {
            $userInfo = $this->oauth->handleFacebookCallback($code);
            $user = $this->findOrCreateUser($userInfo);

            $_SESSION['USER'] = $user->username;
            $_SESSION['login_success'] = true;

            header('Location: /');
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Facebook login failed: ' . $e->getMessage();
            header('Location: /login');
        }
    }

    /**
     * Find or create user from OAuth
     */
    private function findOrCreateUser(array $userInfo): User
    {
        $db = \Database::getInstance()->getConnection();

        // Check if user exists by provider ID
        $field = $userInfo['provider'] . '_id';
        $stmt = $db->prepare("SELECT * FROM users WHERE {$field} = ?");
        $stmt->execute([$userInfo['provider_id']]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);

        if ($user) {
            return $user;
        }

        // Check if user exists by email
        if (!empty($userInfo['email'])) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$userInfo['email']]);
            $user = $stmt->fetch(\PDO::FETCH_OBJ);

            if ($user) {
                // Link OAuth account
                $stmt = $db->prepare("UPDATE users SET {$field} = ?, auth_provider = ? WHERE id = ?");
                $stmt->execute([$userInfo['provider_id'], $userInfo['provider'], $user->id]);
                return $user;
            }
        }

        // Create new user
        $username = $userInfo['provider'] . '_' . $userInfo['provider_id'];
        $stmt = $db->prepare("
            INSERT INTO users (username, hoten, email, {$field}, auth_provider, avatar_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $userInfo['name'],
            $userInfo['email'],
            $userInfo['provider_id'],
            $userInfo['provider'],
            $userInfo['avatar'],
        ]);

        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$db->lastInsertId()]);

        return $stmt->fetch(\PDO::FETCH_OBJ);
    }
}
```

- [ ] **Step 2: Create routes**

```php
<?php
// routes/auth.php
return [
    'GET /auth/google' => [AuthController::class, 'googleRedirect'],
    'GET /auth/google/callback' => [AuthController::class, 'googleCallback'],
    'GET /auth/facebook' => [AuthController::class, 'facebookRedirect'],
    'GET /auth/facebook/callback' => [AuthController::class, 'facebookCallback'],
];
```

- [ ] **Step 3: Commit**

```bash
git add app/Controllers/AuthController.php routes/auth.php
git commit -m "feat: add AuthController for OAuth2 login"
```

---

### Task 5: Login Page UI

**Files:**

- Modify: `administrator/userLogin.php`

- [ ] **Step 1: Add OAuth buttons to login page**

```html
<!-- Add after existing login form -->
<div class="oauth-divider">
  <span>hoặc</span>
</div>

<div class="oauth-buttons">
  <a href="/auth/google" class="btn btn-google">
    <svg width="20" height="20" viewBox="0 0 48 48">
      <path
        fill="#EA4335"
        d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"
      />
      <path
        fill="#4285F4"
        d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"
      />
      <path
        fill="#FBBC05"
        d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"
      />
      <path
        fill="#34A853"
        d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"
      />
    </svg>
    Đăng nhập với Google
  </a>

  <a href="/auth/facebook" class="btn btn-facebook">
    <svg width="20" height="20" viewBox="0 0 48 48">
      <path
        fill="#1877F2"
        d="M24 0C10.745 0 0 10.745 0 24s10.745 24 24 24 24-10.745 24-24S37.255 0 24 0z"
      />
      <path
        fill="white"
        d="M31.5 24h-4.5v14h-5.5V24h-3v-4.5h3v-3c0-3.5 1.5-5.5 5.5-5.5l4 .03V15h-3c-1.5 0-2 1-2 2v3h4.5l-1 4.5z"
      />
    </svg>
    Đăng nhập với Facebook
  </a>
</div>

<style>
  .oauth-divider {
    display: flex;
    align-items: center;
    margin: 20px 0;
  }
  .oauth-divider::before,
  .oauth-divider::after {
    content: "";
    flex: 1;
    border-bottom: 1px solid #ddd;
  }
  .oauth-divider span {
    padding: 0 10px;
    color: #666;
  }
  .oauth-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  .btn-google {
    background: white;
    border: 1px solid #ddd;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 10px;
    border-radius: 5px;
  }
  .btn-google:hover {
    background: #f5f5f5;
  }
  .btn-facebook {
    background: #1877f2;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 10px;
    border-radius: 5px;
  }
  .btn-facebook:hover {
    background: #166fe5;
  }
</style>
```

- [ ] **Step 2: Commit**

```bash
git add administrator/userLogin.php
git commit -m "feat: add OAuth login buttons to login page"
```

---

## Verification

After implementation, verify:

```bash
# Test Google OAuth flow
1. Click "Đăng nhập với Google"
2. Login with Google account
3. Should redirect back to site
4. User should be logged in

# Test Facebook OAuth flow
1. Click "Đăng nhập với Facebook"
2. Login with Facebook account
3. Should redirect back to site
4. User should be logged in

# Check database
SELECT username, email, google_id, facebook_id, auth_provider
FROM users
WHERE auth_provider != 'local';
```

---

## Success Metrics

| Metric            | Before    | Target    |
| ----------------- | --------- | --------- |
| Registration Rate | 100/month | 140/month |
| Login Conversion  | 60%       | 80%       |
| Password Resets   | 20/day    | 10/day    |
