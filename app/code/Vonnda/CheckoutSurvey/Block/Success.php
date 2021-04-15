<?php

namespace Vonnda\CheckoutSurvey\Block;

use Vonnda\CheckoutSurvey\Helper\Data;
use Magento\Framework\View\Element\Template;

class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\CollectionFactory
     */
    protected $checkoutSurveyCollectionFactory;

    /**
     * Success constructor.
     * @param Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param Data $helperData
     * @param \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\CollectionFactory $checkoutSurveyCollectionFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        Data $helperData,
        \Vonnda\CheckoutSurvey\Model\ResourceModel\CheckoutSurvey\CollectionFactory $checkoutSurveyCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
        $this->customerSession = $customerSession;
        $this->checkoutSurveyCollectionFactory = $checkoutSurveyCollectionFactory;
    }

    public function getExtensionHelper()
    {
        return $this->helperData;
    }

    public function getQuestion()
    {
        return $this->helperData->getQuestion();
    }

    public function getAnswerOptions()
    {
        $answerOptions = $this->helperData->getAnswerOptions();
        if ($this->helperData->isRandomizeAnswerOptions()) {
            shuffle($answerOptions);
        }
        return array_map('trim', $answerOptions);
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    public function getCustomerEmail()
    {
        return $this->customerSession->getCustomer()->getEmail();
    }

    public function isCustomerAlreadyAnswer()
    {
        $collection = $this->checkoutSurveyCollectionFactory->create();
        $customerAnswer = $collection->getItemByColumnValue('customer_id', $this->getCustomerId());
        return isset($customerAnswer);
    }

}
