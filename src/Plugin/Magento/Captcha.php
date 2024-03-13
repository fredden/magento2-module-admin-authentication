<?php

namespace Fredden\AdminAuth\Plugin\Magento;

use Magento\Captcha\Model\DefaultModel;
use Magento\Framework\App\RequestInterface;

class Captcha
{
    public function __construct(
        private readonly RequestInterface $request,
    ) {
    }

    public function afterIsRequired(DefaultModel $subject, bool $result): bool
    {
        if (
            $result
            && $this->request->getRouteName() === 'Fredden_AdminAuth'
            && $this->request->getControllerName() === 'login'
            && $this->request->getActionName() === 'refresh'
        ) {
            return false;
        }

        return $result;
    }
}
