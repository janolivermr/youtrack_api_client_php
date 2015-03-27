<?php namespace Youtrack\API;


abstract class YTClientAbstract
{

    protected $client;

    public function __construct(YTClient $client)
    {
        $this->client = $client;
    }
}