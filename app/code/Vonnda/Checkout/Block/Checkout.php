<?php
namespace Vonnda\Checkout\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Checkout\Model\CompositeConfigProvider;
use Aheadworks\OneStepCheckout\Model\Layout\LayoutProcessorProvider;
use Magento\Framework\App\Http\Context as HttpContext;
use Vonnda\Checkout\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Api\CountryInformationAcquirerInterface;
use Magento\Framework\App\ResourceConnection;

use Vonnda\Subscription\Helper\TimeDateHelper;

class Checkout extends \Aheadworks\OneStepCheckout\Block\Checkout
{

    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;
    
    protected $helper;

    protected $timeDateHelper;

    protected $checkoutSession;

    protected $countryInformationAcquirer;

    protected $resourceConnection;

    /**
     * @param Context $context
     * @param FormKey $formKey
     * @param CompositeConfigProvider $configProvider
     * @param LayoutProcessorProvider $layoutProcessorProvider
     * @param HttpContext $httpContext
     * @param Data $helper
     * @param TimeDateHelper $timeDateHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        FormKey $formKey,
        CompositeConfigProvider $configProvider,
        LayoutProcessorProvider $layoutProcessorProvider,
        HttpContext $httpContext,
        Data $helper,
        TimeDateHelper $timeDateHelper,
        CheckoutSession $checkoutSession,
        CountryInformationAcquirerInterface $countryInformationAcquirer,
        ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $formKey, $configProvider, $layoutProcessorProvider, $httpContext, $data);
        $this->helper = $helper;
        $this->timeDateHelper = $timeDateHelper;
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        $this->countryInformationAcquirer = $countryInformationAcquirer;
        $this->resourceConnection = $resourceConnection;
    }

    public function getIsDeviceInCart()
    {
        return $this->helper->isDeviceInCart();
    }

    public function getNextRefillDate()
    {
        return $this->timeDateHelper->getNextDateFromFrequency(6, "month", "m/d/y");
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        $checkoutConfig = $this->configProvider->getConfig();
        $quote = $this->checkoutSession->getQuote();
        $address = $quote->getShippingAddress();

        $checkoutConfig['quoteData']['gift_recipient_email'] = $address->getGiftRecipientEmail();
        $checkoutConfig['countryStateMap'] = $this->getStateMapQuery();
        return $checkoutConfig;
    }

    //kept for reference - getAvailableRegions is inconsistent
    public function getStateMap()
    {
        $countries = $this->countryInformationAcquirer->getCountriesInfo();

        foreach ($countries as $country) {
            // Get regions for this country:
            $regions = [];

            if ($availableRegions = $country->getAvailableRegions()) {
                foreach ($availableRegions as $region) {
                    $regions[] = [
                        'id'   => $region->getId(),
                        'code' => $region->getCode(),
                        'name' => $region->getName()
                    ];
                }
            }
        }

        return $regions;
    }

    public function getStateMapQuery()
    {
        $query = "
            SELECT region_id as id, code, default_name as name FROM directory_country_region WHERE country_id='US' OR country_id='CA'
        ";

        $connection = $this->resourceConnection->getConnection();
        $results = $connection->fetchAll($query);
        return $results;
    }

}