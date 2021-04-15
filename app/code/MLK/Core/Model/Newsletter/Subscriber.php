<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace MLK\Core\Model\Newsletter;

use Magento\Newsletter\Model\Subscriber as CoreSubscriber;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\MailException;
use Magento\Framework\App\ResourceConnection;

class Subscriber extends CoreSubscriber
{

    const XML_PATH_SUCCESS_CONFIRMATION_FLAG = 'newsletter/subscription/success';

    /**
     * @var CustomerInterfaceFactory
     */
    private $customerFactory;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    protected $resourceConnection;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Newsletter\Helper\Data $newsletterData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param \Magento\Framework\Stdlib\DateTime\DateTime|null $dateTime
     * @param CustomerInterfaceFactory|null $customerFactory
     * @param DataObjectHelper|null $dataObjectHelper
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Newsletter\Helper\Data $newsletterData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime = null,
        CustomerInterfaceFactory $customerFactory = null,
        DataObjectHelper $dataObjectHelper = null
    ) {
        $this->customerFactory = $customerFactory ?: ObjectManager::getInstance()
            ->get(CustomerInterfaceFactory::class);
        $this->dataObjectHelper = $dataObjectHelper ?: ObjectManager::getInstance()
            ->get(DataObjectHelper::class);
        $this->resourceConnection = $resourceConnection;

        parent::__construct(
            $context,
            $registry,
            $newsletterData,
            $scopeConfig,
            $transportBuilder,
            $storeManager,
            $customerSession,
            $customerRepository,
            $customerAccountManagement,
            $inlineTranslation,
            $resource,
            $resourceCollection,
            $data,
            $dateTime,
            $customerFactory,
            $dataObjectHelper
        );
    }


    /**
     * Sends out confirmation success email - OVERWRITTEN TO ALLOW CONFIG
     *
     * @return $this
     */
    public function sendConfirmationSuccessEmail()
    {
        $shouldSendConfirmationSuccess = $this->_scopeConfig->getValue(
            self::XML_PATH_SUCCESS_CONFIRMATION_FLAG,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) == 1;

        if(!$shouldSendConfirmationSuccess){
            return $this;
        }

        return parent::sendConfirmationSuccessEmail();
    }

    /**
     * Load subscriber info by customerId - OVERWRITTEN BECAUSE PURCHASE API IN
     * CRON JOB WILL ALWAYS LOAD NULL WITHOUT STORE ID
     *
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerIdAndStore($customerId, $storeId)
    {
        try {
            $customerData = $this->customerRepository->getById($customerId);
            $customerData->setStoreId($storeId);
            $data = $this->getResource()->loadByCustomerData($customerData);
            $this->addData($data);
            if (!empty($data) && $customerData->getId() && !$this->getCustomerId()) {
                $this->setCustomerId($customerData->getId());
                $this->setSubscriberConfirmCode($this->randomSequence());
                $this->save();
            }
        } catch (NoSuchEntityException $e) {
        }
        return $this;
    }

    /**
     * Load subscriber data from resource model by email
     * MODIFIED FROM loadByEmail TO PASS STORE ID EXPLICITLY AND CALL DIRECT QUERY.
     * THE NATIVE LOGIC @371-377 IN THE SUBSCRIBER MODEL DOESN'T WORK IN THE CRON CONTEXT.
     *
     * @param string $subscriberEmail
     * @param int $storeId
     * @return array
     */
    public function loadSubscriberDataByEmailAndStore($subscriberEmail, $storeId)
    {
        if(!$storeId){
            throw new \Exception('Store Id is required');
        }

        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()->from('newsletter_subscriber')
            ->where('store_id=?', $storeId)
            ->where('subscriber_email=?', $subscriberEmail);
        $results = $connection->fetchAll($query);

        if($results){
            return $results[0];
        }

        return null;
    }

}
