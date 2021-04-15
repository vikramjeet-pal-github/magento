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


class RepairSubscriptionState extends Command
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

    protected $repairedSubscriptions = [];

    protected $csvHeaders = [];

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

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new InputOption('dry_run',null, InputOption::VALUE_OPTIONAL, 'dry run - db is not altered'),
            new InputOption('log_debug',null, InputOption::VALUE_OPTIONAL, 'verbose logging')
        ];
        $this->setName('mlk:core:repair_subscriptions_state');
        $this->setDescription('Remove mismatched status/state on subscriptions.');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_null($input->getOption('dry_run'))) {
            if($input->getOption('dry_run') === "true" || $input->getOption('dry_run') === "1"){
                $this->dryRun = (bool)$input->getOption('dry_run');
                $output->writeln("<info>Dry run set to true</info>");
            } else {
                $output->writeln("<info>Dry run set to false</info>");
            }
        }

        if (!is_null($input->getOption('log_debug'))) {
            if($input->getOption('log_debug') === "true" || $input->getOption('log_debug') === "1"){
                $this->logDebug = (bool)$input->getOption('log_debug');
                $output->writeln("<info>Log debug set to true</info>");
            } else {
                $output->writeln("<info>Log debug set to false</info>");
            }
        }

        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $errors = 0;

        $subscriptions = $this->getIncorrectSubscriptionIds();
        if(!$subscriptions){
            $output->writeln('<info>No state/status mismatch detected.</info>');
            return;
        }

        $subscriptionsCount = count($subscriptions);
        $progressBar = new ProgressBar($output, $subscriptionsCount);

        $this->logDebug('Repairing state for ' . $subscriptionsCount . " subscriptions.");

        $totalSubscriptionsRepaired = 0;

        for ($i = 0; $i < $subscriptionsCount; $i++) {
            try {
                $subscription = $this->attemptFix($subscriptions[$i]);
                $this->addSubscriptionToCsv($subscription);
                $totalSubscriptionsRepaired++;
            } catch (\Exception $e) {
                $errors++;
                $this->logDebug("Something went wrong with Subscription ID: " . $subscriptions[$i]['id'] . ".");
                $this->logDebug($e);
            }
            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln('');
        $output->writeln("<comment>Subscription state repair complete - {$totalSubscriptionsRepaired} subscriptions were corrected.</comment>");
        if($this->filePath){
            $output->writeln("<comment>Output csv can be found at {$this->filePath}</comment>");
        }
        if ($errors == 0) {
            $output->writeln('<info>No errors detected!</info>');
        } else {
            $output->writeln("<error>Logged {$errors} error" . ($errors > 1 ? "s" : '') . " to exception.log</error>");
        }
    }

    //Only used in testing
    protected function getAllSubscriptions()
    {
        $connection = $this->resource->getConnection();
        $sql = "SELECT id FROM vonnda_subscription_customer";
        return $connection->fetchAll($sql);
    }

    protected function getIncorrectSubscriptionIds()
    {
        $connection = $this->resource->getConnection();
        $sql = "SELECT id FROM vonnda_subscription_customer WHERE
            (status IN('new_no_payment', 'legacy_no_payment', 'activate_eligible', 'autorenew_off', 'autorenew_complete', 'returned') AND state NOT IN ('inactive')) OR
            (status IN('autorenew_on', 'autorenew_free') AND state NOT IN ('active')) OR
            (status IN('payment_invalid', 'payment_expired', 'processing_error') AND state NOT IN ('error'));";
        return $connection->fetchAll($sql);
    }

    protected function attemptFix($subscriptionId)
    {
        try {
            $subscription = $this->subscriptionCustomerRepository->getById($subscriptionId);
            $subscription->setStatus($subscription->getStatus());
            if(!$this->dryRun){
                $subscription->getResource()->save($subscription);
            }
            return $subscription;
        } catch(\Exception $e){
            $this->logger->critical($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    protected function initCsv($subscription)
    {
        $this->setHeaders($subscription);

        $exportData = [];
        $exportData[] = $this->getHeaders();
        $filename = 'repaired_state_subscriptions';
        $date = (new \DateTime())->format('Y-m-d');
        $path = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . $filename . "_{$date}.csv";
        $this->csv->appendData($path, $exportData, "w");

        $this->csvInitialized = true;
        $this->filePath = $path;
    }

    protected function addSubscriptionToCsv($subscription)
    {
        if(!$this->csvInitialized){
            $this->initCsv($subscription);
        }
        $exportData = [];
        $exportData[] = array_values($subscription->getData());
        $this->csv->appendData($this->filePath, $exportData, "a");
    }

    protected function getHeaders()
    {
        return $this->csvHeaders;
    }

    protected function setHeaders($subscription)
    {
        if($this->csvHeaders){
            return;
        }

        $subscriptionData = $subscription->getData();
        $this->csvHeaders = array_keys($subscriptionData);
    }

    protected function logDebug($message)
    {
        if($this->logDebug){
            $this->logger->info($message);
        }
    }

}
