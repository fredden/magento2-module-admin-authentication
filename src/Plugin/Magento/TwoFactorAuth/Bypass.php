<?php

namespace Fredden\AdminAuth\Plugin\Magento\TwoFactorAuth;

use Fredden\AdminAuth\Scope\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\TwoFactorAuth\Observer\ControllerActionPredispatch;

class Bypass
{
    public function __construct(
        private readonly Config $config,
        private readonly Session $authSession,
    ) {
    }

    public function aroundExecute(
        ControllerActionPredispatch $subject,
        callable $proceed,
        Observer $observer
    ): void {
        if (
            $this->authSession->getFreddenAdminAuth() === true
            && $this->authSession->isLoggedIn()
            && $this->config->isEnabled()
            && $this->config->isAllowedToBypassTwoFactor()
        ) {
            return;
        }

        $proceed($observer);
    }
}
