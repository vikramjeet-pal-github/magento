<?php
namespace Vonnda\AheadworksRma\Block\Adminhtml\Request\Edit\Button;

use Aheadworks\Rma\Block\Adminhtml\Request\Edit\Button\ButtonAbstract;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class PrintLabel extends ButtonAbstract implements ButtonProviderInterface
{

    /** {@inheritdoc} */
    public function getButtonData()
    {
        $button = [];
        try {
            if ($this->context->getRequest()->getParam('id') != null) {
                $request = $this->requestRepository->get($this->context->getRequest()->getParam('id'));
                if ($request->getStatusId() != 7) {
                    $button = [
                        'label' => __('Print Label'),
                        'class' => 'print_label',
                        'on_click' => sprintf("location.href = '%s';", $this->getUrl('vonnda_awrma_admin/rma/printLabel', ['request_id' => $request->getId()])),
                        'sort_order' => 20
                    ];
                }
            }
        } catch (NoSuchEntityException $e) {}
        return $button;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

}