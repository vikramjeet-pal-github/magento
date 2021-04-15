<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace Vonnda\Subscription\Ui\DataProvider\SubscriptionCustomer\Form;

use Vonnda\Subscription\Model\ResourceModel\SubscriptionCustomer\CollectionFactory;
use Vonnda\Subscription\Model\SubscriptionCustomer;
use Vonnda\Subscription\Model\Customer\AddressFactory;
use Vonnda\Subscription\Model\SubscriptionPaymentRepository;
use Vonnda\Subscription\Helper\StripeHelper;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Customer\Api\AddressRepositoryInterface;

class SubscriptionCustomerDataProvider extends AbstractDataProvider
{

    protected $addressFactory;

    protected $subscriptionPaymentRepository;

    protected $addressRepository;

    protected $stripeHelper;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $subscriptionCustomerCollectionFactory
     * @param array $meta
     * @param array $data
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $subscriptionCustomerCollectionFactory,
        array $meta = [],
        array $data = [],
        AddressFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        StripeHelper $stripeHelper
    ) {
        $this->collection = $subscriptionCustomerCollectionFactory->create();
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->stripeHelper = $stripeHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = array();
        /** @var SubscriptionCustomer $subscriptionCustomer */
        foreach ($items as $subscriptionCustomer) {
            try {
                $subscriptionPayment = $this->subscriptionPaymentRepository
                                            ->getById($subscriptionCustomer->getSubscriptionPaymentId());
            } catch(\Exception $e){
                $subscriptionPayment = false;
            }
            $this->loadedData[$subscriptionCustomer->getId()]['subscriptionCustomer'] = $subscriptionCustomer->getData();
            if($subscriptionPayment){
                $this->loadedData[$subscriptionCustomer->getId()]['subscriptionCustomer']['payment_code'] = $subscriptionPayment->getPaymentCode();
            } else {
                $this->loadedData[$subscriptionCustomer->getId()]['subscriptionCustomer']['payment_code'] = "";
            }
        }

        return $this->loadedData;
    }

}