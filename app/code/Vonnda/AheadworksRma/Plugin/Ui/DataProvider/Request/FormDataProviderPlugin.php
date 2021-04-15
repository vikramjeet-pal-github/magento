<?php
namespace Vonnda\AheadworksRma\Plugin\Ui\DataProvider\Request;

class FormDataProviderPlugin
{

    protected $request;
    protected $packageCollectionFactory;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Vonnda\AheadworksRma\Model\ResourceModel\Package\CollectionFactory $packageCollectionFactory
    ) {
        $this->request = $request;
        $this->packageCollectionFactory = $packageCollectionFactory;
    }

    public function afterGetData(\Aheadworks\Rma\Ui\DataProvider\Request\FormDataProvider $subject, $result)
    {
        try {
            $id = $this->request->getParam('id');
            $packageCollection = $this->packageCollectionFactory->create()->addFieldToFilter('request_id', $id);
            $itemLocations = [];
            foreach ($packageCollection as $packageModel) {
                $itemLocations[$packageModel->getItemId()] = $packageModel->getLocation();
            }
            foreach ($result[$id]['order_items'] as $key => $item) {
                $result[$id]['order_items'][$key]['rma_location'] = $itemLocations[$item['item_id']];
            }
        } catch (\Exception $e) {}
        return $result;
    }

}