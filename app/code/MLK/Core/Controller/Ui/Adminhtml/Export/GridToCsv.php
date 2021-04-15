<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */
namespace MLK\Core\Controller\Ui\Adminhtml\Export;

use Magento\Ui\Controller\Adminhtml\Export\GridToCsv as CoreGridToCsv;

/**
 * Class GridToCsv
 */
class GridToCsv extends CoreGridToCsv
{
    /**
     * Overwritten to ignore slow transaction in New Relic
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (extension_loaded('newrelic')) {
            newrelic_ignore_transaction();
        }
        
        return parent::execute();
    }
}
