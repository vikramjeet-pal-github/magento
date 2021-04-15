<?php
/**
 * Redirect.php
 */
declare(strict_types=1);

namespace MLK\Core\Controller\Index;

use Magento\Framework\{
    App\Action\Context,
    Controller\ResultFactory
};
use MLK\Core\Helper\Data as CoreHelper;

class Redirect extends \Magento\Framework\App\Action\Action
{
    /** @property CoreHelper $coreHelper */
    protected $helperData;

    /**
     * @param Context $context
     * @param CoreHelper $coreHelper
     * @return void
     */
    public function __construct(
        Context $context,
        CoreHelper $coreHelper
    ) {
        parent::__construct($context);
        $this->coreHelper = $coreHelper;
    }

    /**
     * @return Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->coreHelper->getRedirectUrl());

        return $resultRedirect;
    }
}
