<?php

namespace Vonnda\Subscription\Controller\Customer;

use Vonnda\Subscription\Helper\Data as Helper;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\SubscriptionService;
use Vonnda\Subscription\Api\Data\SubscriptionCustomerEstimateQueryInterfaceFactory;

use Carbon\Carbon;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutFactory;


//Activate Intro and Fine Print Sections
class GetActivateSections extends Action
{
    /**
     * Subscription Helper
     *
     * @var \Vonnda\Subscription\Helper\Data $helper
     */
    protected $helper;

    /**
     * Customer Repository
     *
     * @var \Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface $customerRepository
     */
    protected $subscriptionCustomerRepository;

    /**
     * Json Factory
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Layout Factory
     *
     * @var \Magento\Framework\View\LayoutFactory $layoutFactory
     */
    protected $layoutFactory;

    /**
     *
     * @var SubscriptionCustomerEstimateQueryInterfaceFactory $subscriptionCustomerEstimateQueryFactory
     */
    protected $subscriptionCustomerEstimateQueryFactory;

    /**
     *
     * @var SubscriptionService $subscriptionService
     */
    protected $subscriptionService;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        LayoutFactory $layoutFactory,
        SubscriptionCustomerEstimateQueryInterfaceFactory $subscriptionCustomerEstimateQueryFactory,
        SubscriptionService $subscriptionService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->layoutFactory = $layoutFactory;
        $this->subscriptionCustomerEstimateQueryFactory = $subscriptionCustomerEstimateQueryFactory;
        $this->subscriptionService = $subscriptionService;

        parent::__construct($context);
    }

    public function execute()
    {
        $isValidRequest = false;
        $result = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParams();
        if (isset($params['subscriptionId']) && $params['subscriptionId']) {
            $isValidRequest = true;
        }

        if ($isValidRequest) {
            try {
                $subscriptionCustomer = $this->subscriptionCustomerRepository->getById((int) $params['subscriptionId']);
                $subscriptionPlan = $subscriptionCustomer->getSubscriptionPlan();
                $price = $this->getSubscriptionPrice($subscriptionCustomer);
                $introBlockContent = $this->getIntroContent($subscriptionCustomer, $subscriptionPlan, $price);
                $finePrintContent = $this->getFinePrintContent($subscriptionCustomer, $subscriptionPlan, $price);
                $response = [
                    'Status' => 'success',
                    'introBlockContent' => $introBlockContent,
                    'finePrintContent' => $finePrintContent
                ];
            } catch (\Exception $e) {
                $response = [
                    'Status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
            return $result->setData($response);
        } else {
            $response = [
                'Status' => 'error',
                'message' => 'Improper request'
            ];
            return $result->setData($response);
        }
    }


    public function getIntroContent($subscriptionCustomer, $subscriptionPlan, $price)
    {
        $nextOrderDate = Carbon::createFromTimeString($subscriptionCustomer->getNextOrder())->setTimezone('America/Los_Angeles');
        $todayDate = Carbon::now()->setTimezone('America/Los_Angeles');
        if ($todayDate->greaterThan($nextOrderDate)){
            $dateString = $todayDate->format("m/d/Y");
        } else {
            $dateString = $nextOrderDate->format("m/d/Y");
        }
       
        

        $blockVariables = [
            'device_sku' => $subscriptionPlan->getDeviceSku(),
            'short_description' => $subscriptionPlan->getShortDescription(),
            'more_info' => $subscriptionPlan->getMoreInfo(),
            'price' => "$" . number_format($price, 2, '.', ','),
            'frequency' => $subscriptionPlan->getFrequency(),
            'frequency_unit' => $subscriptionPlan->getFrequencyUnits() . "s",
            'exp_date' => $dateString
        ];
        
        if($subscriptionCustomer->getStatus() === SubscriptionCustomer::ACTIVATE_ELIGIBLE_STATUS){
            $template = "Vonnda_Subscription::subscription_intro_section_free.phtml";
        } else {
            $template = "Vonnda_Subscription::subscription_intro_section_default.phtml";
        }
        
        $layout = $this->layoutFactory->create();
        $block = $layout
            ->createBlock(
                "Magento\Framework\View\Element\Template",
                "activate_intro_block_section",
                ['data' => $blockVariables]
            )
            ->setData('area', 'frontend')
            ->setTemplate($template)
            ->toHtml();

        return $block;
    }

    public function getFinePrintContent($subscriptionCustomer, $subscriptionPlan, $price)
    {
        $blockVariables = [
            'short_description' => $subscriptionPlan->getShortDescription(),
            'price' => $price,
            'frequency' => $subscriptionPlan->getFrequency(),
            'frequency_unit' => $subscriptionPlan->getFrequencyUnits() . "s"
        ];
        
        if($subscriptionCustomer->getStatus() === SubscriptionCustomer::ACTIVATE_ELIGIBLE_STATUS){
            $template = "Vonnda_Subscription::subscription_fine_print_section_free.phtml";
        } else {
            $template = "Vonnda_Subscription::subscription_fine_print_section_default.phtml";
        }

        $layout = $this->layoutFactory->create();
        $block = $layout
            ->createBlock(
                "Magento\Framework\View\Element\Template",
                "activate_fine_print_section",
                ['data' => $blockVariables]
            )
            ->setData('area', 'frontend')
            ->setTemplate($template)
            ->toHtml();

        return $block;
    }

    public function getSubscriptionPrice($subscriptionCustomer)
    {
        $estimateRequest = $this->subscriptionCustomerEstimateQueryFactory->create();
        $estimateRequest->setSubscriptionId($subscriptionCustomer->getId());
        $estimateRequest->setShippingAddressId($subscriptionCustomer->getShippingAddressId());
        $estimate = $this->subscriptionService->getSubscriptionCustomerEstimate($estimateRequest);
        return $estimate->getSubtotal();
    }
}
