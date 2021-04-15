<?php

namespace Narvar\Accord\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/narvar_accord.log';
}
