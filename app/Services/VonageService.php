<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;


class VonageService
{
    protected $client;

    public function __construct()
    {
        $basic = new Basic(
            config('vonage.key'),
            config('vonage.secret'),
        );

        $this->client = new Client($basic);
    }

    public function send($to, $message)
    {
        return $this->client->sms()->send(
            new SMS($to, config('vonage.from'), $message)
        );
    }
}
