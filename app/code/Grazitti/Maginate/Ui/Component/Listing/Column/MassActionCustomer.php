<?php
/**
 * Copyright © 2020 Grazitti. All rights reserved.
 */

namespace Grazitti\Maginate\Ui\Component\Listing\Column;

class MassActionCustomer extends \Magento\Ui\Component\MassAction
{
    public function prepare()
    {
        parent::prepare();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $signIn = 'grazitti_maginate/general/maginate_lead_sync_on_login';
        $enable = $objectManager->create(\Grazitti\Maginate\Helper\Data::class)->getConfigValue($signIn);
        $config = $this->getConfiguration();
        if ($enable!=1) {
            $allowedActions = [];
            foreach ($config['actions'] as $action) {
                if ('customer_sync' != $action['type']) {
                    $allowedActions[] = $action;
                }
            }
            $config['actions'] = $allowedActions;
        }
        $this->setData('config', $config);
    }
}
