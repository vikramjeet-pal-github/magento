<?php

namespace Narvar\Accord\Helper;

use Zend\Http\Client;
use Narvar\Accord\Logger\Logger as Logger;
use Narvar\Accord\Helper\AccordException;

class NarvarClient
{
    private $client;

    private $logger;

    /**
    * Constructor
    *
    * @param Client        $client        Zend http client.
    * @param Logger        $logger        Narvar\Accord\Logger\Logger
    */
    public function __construct(
        Client $client,
        Logger $logger
    ) {
        $this->client = $client;
        $this->client->setOptions(
            [
                "keepalive"  => true,
                "useragent"  => "narvar_accord",
                "persistent" => true,
                'adapter'    => 'Zend\Http\Client\Adapter\Curl',
            ]
        );
        $this->logger = $logger;
    }

    public function send($api, $method, $body, $headers)
    {
        try {
            $this->client->setUri($api);
            $this->client->setMethod($method);
            $this->client->setRawBody($body);
            $this->client->setHeaders($headers);
            return $this->client->send();
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage() . ' in NarvarClient ' . __METHOD__;
            $this->logger->error($errorMessage);
            throw new AccordException($errorMessage);
        }
    }
}
