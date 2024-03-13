<?php

namespace Fredden\AdminAuth\Scope;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\ClientInterface as HTTPClientInterface;

class Config
{
    private const CACHE_KEY_AUTH_KEYS = 'fredden_adminauth_auth_keys';
    private const XML_PATH_IS_ENABLED = 'admin/fredden_adminauth/enabled';
    private const XML_PATH_ALLOWED_BYPASS_2FA = 'admin/fredden_adminauth/bypass_2fa';
    private const URL_AUTH_KEYS = 'https://auth.fredden.com/keys';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly HTTPClientInterface $httpClient,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function getAuthKeys($canUseCache = true): array
    {
        if ($canUseCache && $cacheEntry = $this->cache->load(self::CACHE_KEY_AUTH_KEYS)) {
            return json_decode($cacheEntry, true, 32, JSON_THROW_ON_ERROR);
        }

        $this->httpClient->get(self::URL_AUTH_KEYS);
        if ($this->httpClient->getStatus() !== 200) {
            return [];
        }

        $keys = json_decode($this->httpClient->getBody(), true, 32, JSON_THROW_ON_ERROR);

        $this->cache->save(json_encode($keys), self::CACHE_KEY_AUTH_KEYS);

        return $keys;
    }

    public function isAllowedToBypassTwoFactor(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOWED_BYPASS_2FA);
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_IS_ENABLED);
    }
}
