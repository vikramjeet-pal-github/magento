<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Customer\Api\CustomerRepositoryInterface;
/**
 * Class SubscriptionCustomerAddress
 */
class SubscriptionCustomerCustomer extends Column
{

    /**
     * Customer Repository Interface
     *
     * @var \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    protected $customerRepository;
 
    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param PriceCurrencyInterface $priceFormatter
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        CustomerRepositoryInterface $customerRepository,
        array $components = [],
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
 
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    //TODO - set to URL
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($item['customer_id']) {
                    $item[$this->getData('name')] = $this->buildCustomerString($item['customer_id']);
                } else {
                    $item[$this->getData('name')] = "Customer info not found for this subscription";
                }
            }
        }
        return $dataSource;
    }

    protected function buildCustomerString($customerId)
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            $customerString = "Id: " . $customer->getId();
            $customerString .= " - " . $customer->getFirstname() . " " . $customer->getLastname();
            $customerString .= " - " . $customer->getEmail();

            return $customerString;
        } catch (\Exception $e) {
            return "Customer not found";
        }
    }
}
