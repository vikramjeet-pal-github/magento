<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Ui\Component\Listing\Column;

class MassActionOrder extends \Magento\Ui\Component\MassAction
{
    public function prepare()
    {
        parent::prepare();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $signIn = 'grazitti_maginate/orderconfig/maginate_order_integration';
        $enable = $objectManager->create(\Grazitti\Maginate\Helper\Data::class)->getConfigValue($signIn);
        
        $config = $this->getConfiguration();
        if ($enable!=1) {
            $allowedActions = [];
            foreach ($config['actions'] as $action) {
                if ('order_sync' != $action['type']) {
                    $allowedActions[] = $action;
                }
            }
            $config['actions'] = $allowedActions;
        }
        $this->setData('config', $config);
    }
}
