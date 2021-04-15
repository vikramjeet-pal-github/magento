<?php
namespace Vonnda\Subscription\Console\Command;

use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Area;
use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
use Vonnda\Subscription\Api\SubscriptionPlanRepositoryInterface;
use Vonnda\Subscription\Helper\Data as SubscriptionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class RepairDuplicateSubscriptions extends Command
{

    protected $orderRepository;
    protected $orderCollectionFactory;
    protected $customerManagement;
    protected $logger;
    protected $state;
    protected $resource;
    protected $subscriptionCustomerRepository;
    protected $subscriptionPlanRepository;
    protected $subscriptionHelper;
    protected $searchCriteriaBuilder;
    protected $csv;
    protected $directoryList;
    protected $repairedOrders = [];
    protected $deletedSubscriptions = [];
    protected $deletedSubscriptionsCount = 0;
    protected $csvInitialized = false;
    protected $filePath;
    protected $logDebug = false;
    protected $dryRun = false;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        State $state,
        ResourceConnection $resource,
        SubscriptionCustomerRepositoryInterface $subscriptionCustomerRepository,
        SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
        SubscriptionHelper $subscriptionHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Csv $csv,
        DirectoryList $directoryList
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->state = $state;
        $this->resource = $resource;
        $this->subscriptionCustomerRepository = $subscriptionCustomerRepository;
        $this->subscriptionPlanRepository = $subscriptionPlanRepository;
        $this->subscriptionHelper = $subscriptionHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->csv = $csv;
        $this->directoryList = $directoryList;
        parent::__construct();
    }

    protected function getSubscriptions()
    {
        $connection = $this->resource->getConnection();
        $sql = "SELECT id, parent_order_id, COUNT(parent_order_id) AS sub_count
                FROM vonnda_subscription_customer
                WHERE created_at >= '2020-09-22 00:00:00'
                GROUP BY parent_order_id
                HAVING sub_count > 1;";
        return $connection->fetchAll($sql);
    }

    /** @inheritDoc */
    protected function configure()
    {
        $options = [
            new InputOption('dry_run',null, InputOption::VALUE_OPTIONAL, 'dry run - db is not altered'),
            new InputOption('log_debug',null, InputOption::VALUE_OPTIONAL, 'verbose logging')
        ];
        $this->setName('mlk:core:repair_duplicate_subscriptions');
        $this->setDescription('Remove duplicate subscriptions.');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($input->getOption('dry_run'))) {
            if ($input->getOption('dry_run') === "true" || $input->getOption('dry_run') === "1") {
                $this->dryRun = (bool)$input->getOption('dry_run');
            }
            $output->writeln("<info>Dry run set to {$this->dryRun}</info>");
        }
        if (!is_null($input->getOption('log_debug'))) {
            if ($input->getOption('log_debug') === "true" || $input->getOption('log_debug') === "1") {
                $this->logDebug = (bool)$input->getOption('log_debug');
            }
            $output->writeln("<info>Log debug set to {$this->logDebug}</info>");
        }
        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $errors = 0;
        $subscriptions = $this->getSubscriptions();
        if ($this->logDebug) {
            //Output array size
            $serialized = serialize($subscriptions);
            $size = mb_strlen($serialized, '8bit');
            $this->logDebug('Size is ' . $size/1000 . ' kb +/-');
        }
        $subscriptionsCount = count($subscriptions);
        $progressBar = new ProgressBar($output, $subscriptionsCount);
        $this->logDebug('Removing duplicates for ' . $subscriptionsCount . " subscriptions.");
        for ($i = 0; $i < $subscriptionsCount; $i++) {
            try {
                $this->attemptFix($subscriptions[$i]);
            } catch (\Exception $e) {
                $errors++;
                $this->logDebug("Something went wrong with Subscription ID: " . $subscriptions[$i]['id'] . ".");
                $this->logDebug($e);
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $totalOrdersChecked = count($this->repairedOrders);
        $output->writeln('');
        $output->writeln("<comment>Duplicate subscription removal complete - {$this->deletedSubscriptionsCount} subscriptions were removed.</comment>");
        $output->writeln("<comment>{$totalOrdersChecked} orders checked.</comment>");
        if ($this->filePath) {
            $output->writeln("<comment>Output csv can be found at {$this->filePath}</comment>");
        }
        $output->writeln("<error>Logged {$errors} error" . ($errors == 0 ? 's' : ($errors > 1 ? 's' : '') . ' to exception.log</error>'));
    }

    protected function attemptFix($subscription)
    {
        try {
            $orderId = $subscription['parent_order_id'];
            $this->logDebug("Fixing order id " . $orderId);
            $order = $this->orderRepository->get($orderId);
            //Get all subscriptions with that particular order id
            $searchCriteria = $this->searchCriteriaBuilder->addFilter('parent_order_id',$orderId,'eq')->create();
            $subscriptionList = $this->subscriptionCustomerRepository->getList($searchCriteria);
            $subscriptions = $subscriptionList->getItems();
            //Get a existing map of the skus and counts for the order
            $subscriptionSkuMap = [];
            foreach ($subscriptions as $subscription) {
                $device = $subscription->getDevice();
                if (isset($subscriptionSkuMap[$device->getSku()])) {
                    $subscriptionSkuMap[$device->getSku()]['subscriptions'][] = $subscription;
                    $subscriptionSkuMap[$device->getSku()]['count'] += 1;
                } else {
                    $subscriptionSkuMap[$device->getSku()] = [
                        'subscriptions' => [$subscription],
                        'count' => 1
                    ];
                }
            }
            //Get a correct map of the skus and counts for the order
            $idealSubscriptionMap = $this->getOrderSkuMap($order);
            $this->logDebug("Ideal map " . json_encode($idealSubscriptionMap));
            foreach ($subscriptionSkuMap as $sku=>$existingSubScriptionsBySku) {
                if (!isset($idealSubscriptionMap[$sku])) {
                    $this->logDebug("Something went wrong");
                    continue;
                }
                if ($existingSubScriptionsBySku['count'] > $idealSubscriptionMap[$sku]) {
                    $this->logDebug("Duplicate subscription found");
                    $excess = $existingSubScriptionsBySku['count'] - $idealSubscriptionMap[$sku];
                    $this->logDebug("An excess of " . $excess . " subscriptions exists for sku " . $sku . " on order id " . $order->getId());
                    $this->removeExcessSubscriptions($existingSubScriptionsBySku, $excess, $order);
                    continue;
                }
                $this->logDebug("All is ok");
            }
            $this->repairedOrders[] = $orderId;
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    protected function removeExcessSubscriptions($existingSubscriptionMap, $excess, $order)
    {
        foreach($existingSubscriptionMap['subscriptions'] as $subscription){
            $subscriptionIds[] = $subscription->getId();
            $subscriptions[$subscription->getId()] = $subscription;
        }
        rsort($subscriptionIds);
        $this->logDebug(json_encode($subscriptionIds));
        $count = 1;
        $initialSubscriptionCount = count($subscriptionIds);
        while ($excess > 0) {
            if ($count > $initialSubscriptionCount) {
                //This shouldn't happen - it means there are more devices with serials set than subscriptions available for deletion
                $this->logDebug("Subscriptions for order " . $order->getId() . " had more serials set on subscriptions than normal");
                break;
            }
            $device = $subscriptions[$subscriptionIds[0]]->getDevice();
            if ($device && $device->getSerialNumber()) {
                $this->logDebug("Subscription id " . $subscriptionIds[0] . " already has a serial number asscoiated (" . $device->getSerialNumber() . ")");
                unset($subscriptionIds[0]);
                $subscriptionIds = array_values($subscriptionIds);
                $count++;
                continue;
            }
            $this->logDebug("Deleting subscription id " . $subscriptionIds[0]);
            if (!$this->csvInitialized) {
                $exportData = [array_keys($subscriptions[$subscriptionIds[0]]->getData())];
                $date = (new \DateTime())->format('Y-m-d');
                $this->filePath = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . "deleted_duplicate_subscriptions{$date}.csv";
                $this->csv->appendData($this->filePath, $exportData, "w");
                $this->csvInitialized = true;
            }
            $exportData = [array_values($subscriptions[$subscriptionIds[0]]->getData())];
            $this->csv->appendData($this->filePath, $exportData, "a");
            $this->deletedSubscriptionsCount++;
            if (!$this->dryRun) {
                $this->subscriptionCustomerRepository->delete($subscriptions[$subscriptionIds[0]]);
            }
            unset($subscriptionIds[0]);
            $subscriptionIds = array_values($subscriptionIds);
            $excess -= 1;
            $count++;
        }
        $this->logDebug("Finished removing duplicates for order id " . $order->getId());
    }

    //The following two functions get the number of skus we should have devices for, for a given order, following
    // the same flow used in SubscriptionManager
    public function getOrderSkuMap($order)
    {
        $itemCollection = $order->getItemsCollection();
        $simpleSkus = [];
        foreach ($itemCollection as $item) {
            if ($item->getProductType() === 'virtual') {
                for ($x=0; $x < $item->getQtyOrdered(); $x++) {
                    $itemSku = $item->getSku();
                    $searchCriteria = $this->searchCriteriaBuilder
                        ->addFilter('trigger_sku',$itemSku,'eq')
                        ->addFilter('store_id',$order->getStoreId(),'eq')
                        ->create();
                    try {
                        $subscriptionPlanList = $this->subscriptionPlanRepository->getList($searchCriteria)->getItems();
                        $subscriptionPlan = $this->subscriptionHelper->returnFirstItem($subscriptionPlanList);
                        if ($subscriptionPlan) {
                            $simpleSku = $subscriptionPlan->getDeviceSku();
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical($e->getMessage());
                        $this->logDebug($e->getMessage());
                    }
                    if (isset($simpleSkus[$simpleSku])) {
                        $simpleSkus[$simpleSku] += 1;
                    } else {
                        $simpleSkus[$simpleSku] = 1;
                    }
                }
            }
        }
        return $simpleSkus;
    }

    protected function logDebug($message)
    {
        if ($this->logDebug) {
            $this->logger->info($message);
        }
    }

}
