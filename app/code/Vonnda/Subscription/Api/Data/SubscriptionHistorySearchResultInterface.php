<?php
 /**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Api\Data;
 
use Magento\Framework\Api\SearchResultsInterface;
 
interface SubscriptionHistorySearchResultInterface extends SearchResultsInterface
{
    /**
     * @return \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface[]
     */
    public function getItems();
 
    /**
     * @param \Vonnda\Subscription\Api\Data\SubscriptionHistoryInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}