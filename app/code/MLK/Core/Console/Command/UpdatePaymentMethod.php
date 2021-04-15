<?php
    namespace MLK\Core\Console\Command;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Helper\ProgressBar;
    use Magento\Framework\App\ResourceConnection;
    use Magento\Framework\App\State;
    use Magento\Framework\Stdlib\DateTime\DateTime;
    use Magento\Framework\Setup\SchemaSetupInterface;
    use Magento\Customer\Api\CustomerRepositoryInterface;
    
    use Vonnda\Subscription\Api\SubscriptionCustomerRepositoryInterface;
    use Vonnda\Subscription\Api\SubscriptionPaymentRepositoryInterface;

    /**
     * Class UpdatePaymentMethod
     */
    class UpdatePaymentMethod extends Command
    {
        const UPDATE_FILE = 'update_file';

        const CSV_FIELDS = [
            'email',
            'magento_stripe_customer_id',
            'magento_subscription_id',
            'stripe_payment_id',
            'credit_card_expiration_month',
            'credit_card_expiration_year'
        ];

        protected $customerRepository;

        protected $subscriptionRepository;

        protected $subscriptionPaymentRepository;

        protected $resourceConnection;

        protected $dateTime;

        protected $state;

        protected $setup;

        protected $paymentUpdates;

        protected $results;

        protected $updateFile;

        protected $count;

        protected $progress;

        /**
         * @inheritDoc
         */
        protected function configure()
        {
            $options = [
                 new InputOption(
                     self::UPDATE_FILE,
                     null,
                     InputOption::VALUE_REQUIRED,
                     'Update CSV File'
                 )
            ];
            $this->setName('mlk:core:update_payment_method');
            $this->setDescription('Update Customers subscription payment method.');
            $this->setDefinition($options);

            parent::configure();
        }

        public function __construct(
            CustomerRepositoryInterface $customerRepository,
            SubscriptionCustomerRepositoryInterface $subscriptionRepository,
            SubscriptionPaymentRepositoryInterface $subscriptionPaymentRepository,
            ResourceConnection $resourceConnection,
            DateTime $dateTime,
            State $state,
            SchemaSetupInterface $setup
        )
        {   
            $this->customerRepository = $customerRepository;
            $this->subscriptionRepository = $subscriptionRepository;
            $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
            $this->resourceConnection = $resourceConnection;
            $this->dateTime = $dateTime;
            $this->state = $state;
            $this->setup = $setup;
            $this->results = [];
            parent::__construct();
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
            if ($file = $input->getOption(self::UPDATE_FILE)) {
                $output->writeln('<info>Provided file path is `' . $file . '`</info>');
                $this->updateFile = $file;
            } else {
                $output->writeln('<error>Please provice an update CSV file.</error>');
            }
            $output->writeln('<info>Loading CSV data.</info>');
            $this->parseCSV();
            $this->createLoggingTable();
            $output->writeln('<info>Updating subscriptions payments.</info>');
            $progressBar = new ProgressBar($output, $this->count);
            $progressBar->start();
            foreach($this->paymentUpdates as $_updateData){
                try {
                    $progressBar->advance();         
                    $this->updateSubscription($_updateData);
                    $this->logSuccess($_updateData);
                } catch (\Exception $e){
                    echo $e->getMessage();
                    $this->logError($_updateData, $e->getMessage());
                }
            }
            $progressBar->finish();
        }

        protected function updateSubscription($updateData)
        {
            $subscription = $this->subscriptionRepository->getById($updateData['magento_subscription_id']);
            if(!isset($subscription)){
                throw new \Exception('Unable to load subscription.');
            }
            $subscriptionPayment = $subscription->getPayment();
            if(!isset($subscriptionPayment)){
                throw new \Exception('Unable to load subscription payment.');
            }
            $customerId = $subscription->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            if(!isset($customer)){
                throw new \Exception('Unable to load customer.');
            }
            $customerEmail = $customer->getEmail();
            if(strtolower($customerEmail) !== strtolower($updateData['email'])){
                throw new \Exception('Customers email dose not match email provided in CSV.');
            }
            $stripeCustomerId  = $subscriptionPayment->getStripeCustomerId();
            if($stripeCustomerId){
                $stripeCustomerData = $this->getStripeCustomerData($stripeCustomerId);
            } else {
                $stripeCustomerData = $this->getStripeCustomerDataByCustomerId($subscription->getCustomerId());
            }
            if(!isset($stripeCustomerData) || !isset($stripeCustomerData['stripe_id'])){
                $stripeProfileData = [
                    'customer_id' => $subscription->getCustomerId(),
                    'stripe_id' => $updateData['magento_stripe_customer_id'],
                    'email' => $updateData['email']
                ];
                $this->createStripeProfileForCustomer($stripeProfileData);
                $stripeCustomerData = $this->getStripeCustomerDataByCustomerId($subscription->getCustomerId());
                $subscriptionPayment->setStripeCustomerId($stripeCustomerData['id']);
            }
            if($stripeCustomerData['stripe_id'] !== $updateData['magento_stripe_customer_id']){
                $stripeCustomerData['stripe_id'] = $updateData['magento_stripe_customer_id'];
                $this->updateStripeCustomerData($stripeCustomerData);
            }
            if(strlen($updateData['credit_card_expiration_month']) < 2){
                $creditCardExpirationDate = '0' . $updateData['credit_card_expiration_month'] . '/' . $updateData['credit_card_expiration_year'];
            } else {
                $creditCardExpirationDate = $updateData['credit_card_expiration_month'] . '/' . $updateData['credit_card_expiration_year'];
            }
            $subscriptionPayment->setPaymentCode($updateData['stripe_payment_id']);
            $subscriptionPayment->setExpirationDate($creditCardExpirationDate);
            $subscriptionPayment->setStatus('valid');
            $subscriptionPayment->setUpdatedAt($this->dateTime->gmtDate());
            $this->subscriptionPaymentRepository->save($subscriptionPayment);
            if($subscription->getStatus() == 'new_no_payment'){
                $subscription->setStatus('autorenew_on');
                $this->subscriptionRepository->save($subscription);
            }
        }

        protected function getStripeCustomerData($id)
        {
            $connection = $this->resourceConnection->getConnection();
            $query = "
                SELECT *
                FROM cryozonic_stripe_customers
                WHERE id = " . $id . ";";
            if($data = $connection->fetchRow($query)){
                return $data;
            }
            return false;
        }

        protected function getStripeCustomerDataByCustomerId($id)
        {
            $connection = $this->resourceConnection->getConnection();
            $query = "
                SELECT *
                FROM cryozonic_stripe_customers
                WHERE customer_id = " . $id . ";";
            if($data = $connection->fetchRow($query)){
                return $data;
            }
            return false;
        }

        protected function createStripeProfileForCustomer($data)
        {
            $connection = $this->resourceConnection->getConnection();
            $query = "
                INSERT
                INTO cryozonic_stripe_customers
                (customer_id, stripe_id, customer_email)
                VALUES ('" . $data['customer_id'] . "','" . $data['stripe_id'] . "','" . $data['email'] . "');
            ";
            return $connection->query($query);
        }

        protected function updateStripeCustomerData($data)
        {
            $connection = $this->resourceConnection->getConnection();
            $query = "
                UPDATE cryozonic_stripe_customers
                SET cryozonic_stripe_customers.stripe_id = '" . $data['stripe_id'] . "'
                WHERE id = " . $data['id'] . ";";
            if($connection->query($query)){
                return true;
            }
            return false;
        }

        protected function parseCSV()
        {
            $row = 0;
            $this->paymentUpdates = [];
            if (($handle = fopen($this->updateFile, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if($row > 0 && isset($data[0])){
                        $paymentData = [];
                        $index = 0;
                        foreach(self::CSV_FIELDS as $field){
                            $paymentData[$field] = isset($data[$index]) ? $data[$index] : null;
                            $index++;
                        } 
                        $this->paymentUpdates[] = $paymentData;
                    }
                    $row++;
                }
                fclose($handle);
            }
            $this->count = count($this->paymentUpdates);
            $this->progress = 0;
        }

        protected function createLoggingTable()
        {        
            //Create logging table
            $tableName = 'tmp_subscription_payment_update_status';
            $connection = $this->setup->getConnection();
            $table = $connection->newTable($this->setup->getTable($tableName))
                ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Primary Key'
                )->addColumn(
                    'processing_status',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'processing_status'
                )->addColumn(
                    'processing_error',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'processing_error'
                )->addColumn(
                    'magento_subscription_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    10,
                    ['nullable' => true, 'default' => null],
                    'Id'
                )->addColumn(
                    'email',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Customer Email'
                )->addColumn(
                    'magento_stripe_customer_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Stripe Customer Token'
                )->addColumn(
                    'stripe_payment_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Stripe Payment Token'
                )->addColumn(
                    'credit_card_expiration_month',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Credit Card Expiration Month'
                )->addColumn(
                    'credit_card_expiration_year',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => true, 'default' => null],
                    'Credit Card Expiration Year'
                );
            $table->setComment('Temp  status table for subscription payment updates.');
            $connection->createTable($table);  
            $this->setup->endSetup();      
        }

        protected function logSuccess($data)
        {
            $query = "
                INSERT INTO tmp_subscription_payment_update_status (processing_status, processing_error, magento_subscription_id, email, magento_stripe_customer_id, stripe_payment_id, credit_card_expiration_month, credit_card_expiration_year)
                VALUES ('success', '','" . $data['magento_subscription_id'] ."', '" .$data['email'] . "', '" . $data['magento_stripe_customer_id'] . "', '" . $data['stripe_payment_id'] . "', '" . $data['credit_card_expiration_month'] . "', '" . $data['credit_card_expiration_year'] . "');";
            $this->resourceConnection->getConnection('core_write')->query($query);
        }

        protected function logError($data, $error)
        {
            $query = "
                INSERT INTO tmp_subscription_payment_update_status (processing_status, processing_error, magento_subscription_id, email, magento_stripe_customer_id, stripe_payment_id, credit_card_expiration_month, credit_card_expiration_year)
                VALUES ('error', '" . $error . "','" . $data['magento_subscription_id'] ."', '" .$data['email'] . "', '" . $data['magento_stripe_customer_id'] . "', '" . $data['stripe_payment_id'] . "', '" . $data['credit_card_expiration_month'] . "', '" . $data['credit_card_expiration_year'] . "');";
            $this->resourceConnection->getConnection('write')->query($query);
        }
    
    }