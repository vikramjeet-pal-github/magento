<?php
namespace Vonnda\StripePayments\Logger;

class Logger extends \Monolog\Logger
{

    public function __construct(Handler $handler)
    {
        parent::__construct('stripeL3Logger', [$handler]);
    }

}