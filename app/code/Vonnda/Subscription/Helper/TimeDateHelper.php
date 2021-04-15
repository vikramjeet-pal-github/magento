<?php
/**
 * @copyright: Copyright © 2019 Vonnda, LLC. All rights reserved.
 * @author   : Vonnda Digital Agency <hello@vonnda.com>
 */

namespace Vonnda\Subscription\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;

use Carbon\Carbon;

class TimeDateHelper extends AbstractHelper
{

    const LOG_DEBUG = false;

    protected $logger;

    public function __construct(
        Context $context,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        parent::__construct($context);
    }
    /**
     * The date format for the functions below is using the 12-hour format, which in this case is to our advantage.
     * The date is created using createMidnightDate, so the hour will be 12, but when magento saves the date, it seems to convert it for some reason.
     * So if the time were 24 hour, the hours would roll back and roll the date back a day. Using 12-hour format avoids that.
     */
    public function getNextMonthsDate()
    {
        return Carbon::createMidnightDate()->settings(['monthOverflow' => false])
            ->addMonth()
            ->format("Y-m-d h:i:s");
    }

    //Considers Timezone
    public function getNextDateFromFrequency($frequency, $frequencyUnits, $format = "Y-m-d h:i:s")
    {
        $nowUTC = Carbon::now();
        $nowPST = Carbon::now("America/Los_Angeles");

        $this->logDebug("Getting Next Run Date");
        $this->logDebug("Now PST " . $nowPST->toDateTimeString());
        $this->logDebug("Now UTC " . $nowUTC->toDateTimeString());

        $nextDate = Carbon::createMidnightDate()->settings(['monthOverflow' => false]);

         if(!$nowUTC->isSameDay($nowPST)){
            $nextDate->subDay();
            $this->logDebug("Days are different - subtracting a day");
        } else {
            $this->logDebug("Days are the same");
        }

        //Make sure monthOverflow is respected
        if($frequencyUnits === 'month' || $frequencyUnits === 'months'){
            $result = $nextDate->addMonths($frequency)->format($format);
        } else {
            $result = $nextDate->add($frequency, $frequencyUnits)->format($format);
        }
        
        $this->logDebug("Setting next refill date to " . $result . " UTC");
        return $result;
    }

    //Where the start date is already a Carbon object
    public function getNextDateFromFrequencyWithStart($frequency, $frequencyUnits, $startDate)
    {
        return $startDate->settings(['monthOverflow' => false])
            ->add($frequency, $frequencyUnits)
            ->format("Y-m-d h:i:s");
    }

    public function isCardExpired($month, $year)
    {
        $now = Carbon::createMidnightDate();
        //One month later to give tme the whole month
        $expDate = Carbon::createMidnightDate($year, $month, 1)->addMonth();
        if($expDate < $now){
            return true;
        } else {
            return false;
        }
    }

    //Takes the format XX/XX
    public function isCardExpiredDateString($dateString)
    {
        $dateArr = explode("/", $dateString);
        if(isset($dateArr[0]) && isset($dateArr[1])){
            $month = $dateArr[0];
            $year = $dateArr[1];
            $now = Carbon::createMidnightDate();
            //One month later to give tme the whole month
            $expDate = Carbon::createMidnightDate($year, $month, 1)->addMonth();
            if($expDate < $now){
                return true;
            } else {
                return false;
            }
        }
    }

    public function durationIsValid($duration)
    {
        try {
            $futureDate =  Carbon::now()->add($duration)->toDateTimeString();
            return true;
        } catch(\Exception $e){
            return false;
        }
    }

    protected function logDebug($message)
    {
        if(self::LOG_DEBUG){
            $this->logger->info($message);
        }
    }

}