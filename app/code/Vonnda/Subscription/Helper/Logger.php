<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Vonnda\Subscription\Logger\CronLogger;
use Vonnda\Subscription\Logger\DebugLogger;
use Vonnda\Subscription\Logger\SubscriptionManagerLogger;
use Vonnda\Subscription\Logger\DebugTenDayEmailLogger;
use Vonnda\Subscription\Logger\DebugThirtyDayEmailLogger;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;

class Logger extends AbstractHelper
{

    protected $coreLogger;

    protected $cronLogger;

    protected $debugLogger;

    protected $subscriptionManagerLogger;

    protected $debugTenDayEmailLogger;
    
    protected $debugThirtyDayEmailLogger;

    public function __construct(
        LoggerInterface $coreLogger,
        CronLogger $cronLogger,
        DebugLogger $debugLogger,
        SubscriptionManagerLogger $subscriptionManagerLogger,
        DebugTenDayEmailLogger $debugTenDayEmailLogger,
        DebugThirtyDayEmailLogger $debugThirtyDayEmailLogger
    )
    {
        $this->coreLogger = $coreLogger;
        $this->cronLogger = $cronLogger;
        $this->debugLogger = $debugLogger;
        $this->subscriptionManagerLogger = $subscriptionManagerLogger;
        $this->debugTenDayEmailLogger = $debugTenDayEmailLogger;
        $this->debugThirtyDayEmailLogger = $debugThirtyDayEmailLogger;
    }

    public function critical($message)
    {
        return $this->coreLogger->critical($message);
    }

    public function info($message)
    {
        return $this->coreLogger->info($message);
    }

    public function debug($message)
    {
        return $this->coreLogger->debug($message);
    }

    //TODO - override addRecord to not use channel name
    public function logToSubscriptionDebug($message)
    {
        return $this->debugLogger->info($message);
    }

    public function logToSubscriptionCron($message)
    {
        return $this->cronLogger->info($message);
    }

    public function logToSubscriptionManager($message)
    {
        return $this->subscriptionManagerLogger->info($message);
    }

    public function logToTenDayEmailDebugLog($message)
    {
        return $this->debugTenDayEmailLogger->info($message);
    }

    public function logToThirtyDayEmailDebugLog($message)
    {
        return $this->debugThirtyDayEmailLogger->info($message);
    }

}