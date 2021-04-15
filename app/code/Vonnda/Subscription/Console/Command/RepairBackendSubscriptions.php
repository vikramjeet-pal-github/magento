<?php
    namespace Vonnda\Subscription\Console\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Helper\ProgressBar;
    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * Class RepairBackendSubscriptions
     */
    class RepairBackendSubscriptions extends Command
    {
        protected $orderAddressCollectionFactory;
        protected $orderRepository;
        protected $orderCollectionFactory;
        protected $customerManagement;
        protected $logger;
        private $state;
        protected $resource;
        public function __construct(
            \Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory $orderAddressCollectionFactory,
            \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
            \Vonnda\Subscription\SubscriptionRepairLogger\Logger $logger,
            \Magento\Framework\App\State $state,
            \Magento\Framework\App\ResourceConnection $resource
        ) {
            $this->orderAddressCollectionFactory = $orderAddressCollectionFactory;
            $this->orderRepository = $orderRepository;
            $this->logger = $logger;
            $this->state = $state;
            $this->resource = $resource;
            parent::__construct();
        }
        
        protected function getReparableSubscriptions()
        {
            $connection = $this->resource->getConnection();
            $sql = "SELECT sub.* ";
            $sql .= "from vonnda_subscription_customer as sub ";
            $sql .= "left join sales_order as sales on sub.parent_order_id = sales.entity_id ";
            $sql .= "left join sales_order_payment as payment on payment.parent_id = sales.entity_id ";
            $sql .= "where sub.created_at >= '2019-10-15 00:00:00' AND sales.order_tag_id != '0' ";
            $sql .= "AND payment.method = 'cryozonic_stripe' AND sub.status = 'new_no_payment' AND sub.parent_order_id is not NULL;";
            return $connection->fetchAll($sql);
        }

        /**
         * @inheritDoc
         */
        protected function configure()
        {
            $options = [];
            $this->setName('mlk:core:repair_backend_subscriptions');
            $this->setDescription('Fix subscriptions which were incorrectly set to new_no_payment.');
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
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
            $progressBar = new ProgressBar($output);
            $errors = 0;
            $connection = $this->resource->getConnection();
            $reparableSubscriptions = $this->getReparableSubscriptions();
            $reparableSubscriptionsCount = count($reparableSubscriptions);
            $this->logger->info('Attempting to fix: ' . $reparableSubscriptionsCount . " subscriptions.");
            for ($i = 0; $i < $reparableSubscriptionsCount; $i++) {
                try {
                    $this->attemptFix($reparableSubscriptions[$i]);
                } catch (\Exception $e) {
                    $errors++;
                    $this->logger->info("Subscription ID: " . $reparableSubscriptions[$i]['id'] . " failed to fix: ");
                    $this->logger->info("Subscription: " . json_encode($reparableSubscriptions[$i]));
                    $this->logger->info($e);
                }
                $progressBar->advance();
            }
            
            $output->writeln('');
            $output->writeln('<comment>Fix attempt complete.</comment>');
            if ($errors == 0) {
                $output->writeln('<info>No errors detected!</info>');
            } else {
                $output->writeln("<error>Logged {$errors} error" . ($errors > 1 ? "s" : '') . " to subscription_repairs.log</error>");
            }
        }

        protected function attemptFix($subscription) 
        {
            $connection = $this->resource->getConnection();
            $connection->beginTransaction();
            try {
                $orderId = $subscription['parent_order_id'];
                $salesOrderPayment = $this->getOrderPaymentFromOrderId($orderId);
                $stripeToken = $this->getStripeTokenFromOrderPayment($salesOrderPayment);
                $subscriptionPaymentId = $this->insertSubscriptionPaymentForCustomer($subscription, $salesOrderPayment);
                $this->fixSubscriptionCustomer($subscription, $subscriptionPaymentId);
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollback();
                throw $e;
            }
        }

        protected function getStripeTokenFromOrderPayment($payment)
        {
            if (!empty($payment) && isset($payment['additional_information'])) {
                $additionalInfo = json_decode($payment['additional_information']);
                if (isset($additionalInfo->stripejs_token)) {
                    return $additionalInfo->stripejs_token;
                }
            }
            throw new \Exception('Stripe token not set.');
        }

        protected function fixSubscriptionCustomer($subscriptionCustomer, $subscriptionPaymentId)
        {
            $sql = "UPDATE vonnda_subscription_customer SET state = 'active', status = 'autorenew_on', subscription_payment_id = $subscriptionPaymentId WHERE id = {$subscriptionCustomer['id']};";
            $this->resource->getConnection()->query($sql);
        }

        protected function insertSubscriptionPaymentForCustomer($subscriptionCustomer, $salesOrderPayment)
        {
            $stripeCustomer = $this->getStripeCustomerFromCustomerId($subscriptionCustomer['customer_id']);
            $status = 'valid';
            $paymentCode = json_decode($salesOrderPayment['additional_information'])->stripejs_token;
            $sql = "INSERT INTO vonnda_subscription_payment (stripe_customer_id,payment_id,status,payment_code) VALUES (?,?,?,?)";
            $this->resource->getConnection()->query($sql, [$stripeCustomer['id'], $salesOrderPayment['entity_id'], $status, $paymentCode]);
            return $this->resource->getConnection()->query('SELECT LAST_INSERT_ID()')->fetch()['LAST_INSERT_ID()'];
        }

        protected function getOrderPaymentFromOrderId($orderId)
        {
            $sql = "SELECT * FROM sales_order_payment WHERE parent_id = $orderId";
            $orderPayments = $this->resource->getConnection()->fetchAll($sql);
            if (count($orderPayments) > 0) {
                return $orderPayments[0];
            }
            throw new \Exception('Order payment not found.');
        }

        protected function getStripeCustomerFromCustomerId($customerId)
        {
            $sql = "SELECT * FROM cryozonic_stripe_customers WHERE customer_id = $customerId";
            $stripeCustomers = $this->resource->getConnection()->fetchAll($sql);
            if (count($stripeCustomers) > 0) {
                return $stripeCustomers[0];
            }
            throw new \Exception('Stripe customer not found.');
        }

    }