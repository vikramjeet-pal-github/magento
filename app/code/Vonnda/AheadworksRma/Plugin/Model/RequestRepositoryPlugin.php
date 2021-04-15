<?php
namespace Vonnda\AheadworksRma\Plugin\Model;

class RequestRepositoryPlugin
{

    protected $request;
    protected $packageFactory;
    protected $packageResourceModel;
    protected $packageCollectionFactory;
    protected $labelCreator;
    protected $orderRepository;
    protected $locations;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Vonnda\AheadworksRma\Model\PackageFactory $packageFactory,
        \Vonnda\AheadworksRma\Model\ResourceModel\Package $packageResourceModel,
        \Vonnda\AheadworksRma\Model\ResourceModel\Package\CollectionFactory $packageCollectionFactory,
        \Vonnda\AheadworksRma\Model\Shipping\FedExRmaCarrier $labelCreator,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Vonnda\AheadworksRma\Helper\Locations $locations,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->packageFactory = $packageFactory;
        $this->packageResourceModel = $packageResourceModel;
        $this->packageCollectionFactory = $packageCollectionFactory;
        $this->labelCreator = $labelCreator;
        $this->orderRepository = $orderRepository;
        $this->locations = $locations;
        $this->logger = $logger;
    }

    public function aroundSave(\Aheadworks\Rma\Model\RequestRepository $subject, \Closure $proceed, $request)
    {
        $value = $proceed($request);
        $params = $this->request->getPostValue();
        if ($params['newRequest'] == 'true') { // yes, it is a string. no, i dont know why.
            $orderCountry = $this->orderRepository->get($params['order_id'])->getShippingAddress()->getCountryId();
            foreach ($params['order_items'] as $item) {
                if ($this->locations->getLocationData($item['rma_location'], 'country') !== $orderCountry) {
                    throw new \Exception('The selected location would cause the package to ship internationally, which is currently unsupported.');
                }
                $data = [
                    'request_id' => $value->getId(),
                    'order_id' => $params['order_id'],
                    'item_id' => $item['item_id'],
                    'item_sku' => $item['sku'],
                    'item_name' => $item['name_label'],
                    'item_qty' => $item['qty'],
                    'location' => $item['rma_location']
                ];
                $package = $this->packageFactory->create();
                $package->setData($data);
                $this->packageResourceModel->save($package);
            }
        } else {
            $packages = $this->packageCollectionFactory->create()->addFieldToFilter('request_id', $value->getId());
            if ($packages->count() !== count($params['order_items'])) {
                foreach ($params['order_items'] as $item) { // repeat the loop used for new requests above, with an added if statement
                    if ($packages->count() != 0) {
                        $rmaItem = $this->packageCollectionFactory->create()->addFieldToFilter('item_id', $item['id']);
                        if ($rmaItem->count() != 0) {
                            continue;
                        }
                    }
                    $data = [
                        'request_id' => $value->getId(),
                        'order_id' => $params['order_id'],
                        'item_id' => $item['item_id'],
                        'item_sku' => $item['sku'],
                        'item_name' => $item['name_label'],
                        'item_qty' => $item['qty'],
                        'location' => $item['rma_location']
                    ];
                    $package = $this->packageFactory->create();
                    $package->setData($data);
                    $this->packageResourceModel->save($package);
                }
                $packages = $this->packageCollectionFactory->create()->addFieldToFilter('request_id', $value->getId());
            }
            foreach ($packages as $pkg) {
                if ($pkg->getShippingLabel()) continue;
                try {
                    $labelData = $this->labelCreator->requestToShipment($pkg);
                    $package = $this->packageFactory->create();
                    $package->setData([
                        'package_id' => $pkg->getPackageId(),
                        'track_number' => $labelData['tracking_number'],
                        'shipping_label' => $labelData['label_content']
                    ]);
                    $this->packageResourceModel->save($package);
                } catch (\Exception $e) {
                    $this->logger->critical('Error generating package shipping labels.', ['exception' => $e]);
                }
            }
        }
        return $value;
    }

}