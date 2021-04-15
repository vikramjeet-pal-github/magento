<?php
namespace Potato\Zendesk\Plugin;

use Potato\Zendesk\Model\Config;
use Potato\Zendesk\Api\SsoManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;

class CustomerSession
{
    /** @var Config */
    protected $config;

    /** @var SsoManagementInterface */
    protected $ssoManagement;

    /** @var RequestInterface */
    protected $request;

    /** @var ResponseInterface */
    protected $response;

    /**
     * @param Config $config
     * @param SsoManagementInterface $ssoManagement
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct(
        Config $config,
        SsoManagementInterface $ssoManagement,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->config = $config;
        $this->ssoManagement = $ssoManagement;
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @param \Magento\Customer\Model\Session $subject
     * @param \Closure $proceed
     * @param null $loginUrl
     *
     * @return mixed
     */
    public function aroundAuthenticate(
        \Magento\Customer\Model\Session $subject,
        \Closure $proceed,
        $loginUrl = null
    ) {;
        $result = $proceed($loginUrl);
        if ($result) {
            return $result;
        }
        if (!$this->config->isSsoEnabled()) {
            return $result;
        }
        $customerId = $this->request->getParam('external_id', null);
        if (null === $customerId) {
            return $result;
        }
        $url = $this->ssoManagement->getLogoutUrl($customerId);
        if (null === $url) {
            return $result;
        }
        $this->response->setRedirect($url);
        return $result;
    }
}