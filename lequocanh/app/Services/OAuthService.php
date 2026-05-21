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
            'email' => $userInfo['email'] ?? null,
            'name' => $userInfo['name'] ?? '',
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Google OAuth error: HTTP {$httpCode}");
        }
        
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
            'name' => $userInfo['name'] ?? '',
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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \RuntimeException("Facebook OAuth error: HTTP {$httpCode}");
        }
        
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
        $configPath = __DIR__ . '/../../config/oauth.php';
        
        if (!file_exists($configPath)) {
            return new self(['google' => ['enabled' => false], 'facebook' => ['enabled' => false]]);
        }
        
        $config = require $configPath;
        return new self($config);
    }
    
    /**
     * Check if Google OAuth is enabled
     */
    public function isGoogleEnabled(): bool
    {
        return $this->config['google']['enabled'] 
            && !empty($this->config['google']['client_id']);
    }
    
    /**
     * Check if Facebook OAuth is enabled
     */
    public function isFacebookEnabled(): bool
    {
        return $this->config['facebook']['enabled'] 
            && !empty($this->config['facebook']['client_id']);
    }
}