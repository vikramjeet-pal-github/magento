<?php

namespace Vonnda\CheckoutSurvey\Controller\Index;

use Vonnda\TealiumTags\Helper\Data as TealiumDataHelper;
use Vonnda\CheckoutSurvey\Model\CheckoutSurvey;

use Magento\Framework\UrlInterface;


class Register extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Vonnda\CheckoutSurvey\Model\CheckoutSurveyFactory
     */
    protected $checkoutSurveyFactory;

    /**
     * @var \Vonnda\CheckoutSurvey\Model\CheckoutSurveyRepository
     */
    protected $checkoutSurveyRepository;

    /**
     * @var \Vonnda\TealiumTags\Helper\Data
     */
    protected $tealiumDataHelper;

    protected $currentUrl;

    /**
     * Register constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Vonnda\CheckoutSurvey\Model\CheckoutSurveyFactory $checkoutSurveyFactory
     * @param \Vonnda\CheckoutSurvey\Model\CheckoutSurveyRepository $checkoutSurveyRepository
     * @param \Vonnda\TealiumTags\Helper\Data $tealiumDataHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Vonnda\CheckoutSurvey\Model\CheckoutSurveyFactory $checkoutSurveyFactory,
        \Vonnda\CheckoutSurvey\Model\CheckoutSurveyRepository $checkoutSurveyRepository,
        TealiumDataHelper $tealiumDataHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSurveyFactory = $checkoutSurveyFactory;
        $this->checkoutSurveyRepository = $checkoutSurveyRepository;
        $this->tealiumDataHelper = $tealiumDataHelper;
        $this->currentUrl = $context->getUrl()->getCurrentUrl();
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $requestData = $this->getRequest()->getParams();

            /** @var CheckoutSurvey $checkoutSurvey */
            $checkoutSurvey = $this->checkoutSurveyFactory->create();
            $checkoutSurveyData = $checkoutSurvey->getDataModel();

            $formattedAnswer = str_replace("_", " ", ucwords($requestData['answer']));
            $checkoutSurveyData->setAnswer($formattedAnswer);
            $checkoutSurveyData->setCustomerId($requestData['customerId']);
            $checkoutSurveyData->setCustomerEmail($requestData['customerEmail']);

            if ($requestData['answer'] == "other") {
                $checkoutSurveyData->setAnswerDetails($requestData['answerDetails']);
            }

            $this->checkoutSurveyRepository->save($checkoutSurveyData);

            $response = ['tealiumData' => $this->setTealiumData($requestData)];
            return $this->jsonResponse($response);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }

    public function setTealiumData($requestData)
    {
        $utagData = [
            'tealium_event' => 'awareness_source',
            'event_category' => 'Survey',
            'event_action' => 'Selected Awareness Source',
            'event_label' => $requestData['answer'],
            'awareness_source' => $requestData['answer'],
            'page_type' => 'checkout',
            'page_url' => $this->currentUrl,
            'ab_test_group' => '',
            'offer_name' => ''
        ];

        $utagData = array_merge($utagData, 
            $this->tealiumDataHelper->getCartInfo(),
            $this->tealiumDataHelper->getCustomerFieldsFromSession());

        $utagData = $this->tealiumDataHelper->setShippingAddressFieldsFromLastOrder($utagData);
        $utagData = $this->tealiumDataHelper->setBillingAddressFieldsFromLastOrder($utagData);

        return $utagData;
    }
}
