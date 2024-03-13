<?php

namespace Fredden\AdminAuth\Controller\Adminhtml\Login;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Fredden\AdminAuth\Scope\Config;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\Auth as AuthController;
use Magento\Backend\Model\Auth as AuthModel;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPost;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use Throwable;

/**
 * This controller is thusly named to as a hack. 'refresh' is one of few names that
 * are not subject to authentication verification, which is exactly what this
 * controller is designed to bypass.
 *
 * @see \Magento\Backend\App\Action\Plugin\Authentication::$_openActions
 */
class Refresh extends AuthController implements HttpPost
{
    public function __construct(
        Context $context,
        private readonly AuthModel $authModel,
        private readonly AuthSession $authSession,
        private readonly Config $config,
        private readonly JsonFactory $resultJsonFactory,
        private readonly UserFactory $userFactory,
        private readonly UserResource $userResource,
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        $token = $this->getRequest()->getPost('token');

        try {
            $payload = $this->parseToken($token);
        } catch (Exception $e) {
            $result = $this->resultJsonFactory->create();
            return $result->setData(
                [
                    'status' => 'failure',
                    'message' => 'Unable to verify token - ' . $e->getMessage(),
                ]
            );
        }

        $errors = [];
        foreach ($payload['emails'] as $emailAddress) {
            $user = $this->userFactory->create();
            $user->setEmail($emailAddress);
            $data = $this->userResource->userExists($user);

            if (!$data) {
                $errors[$emailAddress] = 'No account for ' . $emailAddress;
                continue;
            }

            // Existing user found
            $user->load($data['user_id']);

            if (!$user->getIsActive()) {
                $errors[$emailAddress] = 'Account for ' . $emailAddress . ' is disabled.';
                continue;
            }

            return $this->doLogin($user);
        }

        if ($errors === []) {
            $errors[] = 'No email addresses provided';
        }

        $result = $this->resultJsonFactory->create();
        return $result->setData(
            [
                'status' => 'failure',
                'message' => implode("\n\n", $errors),
            ]
        );
    }

    private function doLogin(User $user): ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        $password = bin2hex(random_bytes(32));
        $user->setPassword($password);
        $user->setPasswordConfirmation($password);
        $user->save();

        try {
            $this->authModel->login($user->getUserName(), $password);
        } catch (Exception $e) {
            return $result->setData(
                [
                    'status' => 'failure',
                    'message' => $e->getMessage(),
                    'username' => $user->getUserName(),
                    'password' => $password,
                ]
            );
        }

        // This is used elsewhere in this module to restrict other features to
        // only when logging in via this tool.
        $this->authSession->setFreddenAdminAuth(true);

        return $result->setData(
            [
                'status' => 'success',
                'message' => 'Logged in as ' . $user->getUserName(),
                'username' => $user->getUserName(),
                'password' => $password,
            ]
        );
    }

    private function parseToken(string $token): array
    {
        try {
            $keys = JWK::parseKeySet(['keys' => $this->config->getAuthKeys()]);
            return (array) JWT::decode($token, $keys);
        } catch (Throwable) {
            $keys = JWK::parseKeySet(['keys' => $this->config->getAuthKeys(false)]);
            return (array) JWT::decode($token, $keys);
        }
    }
}
