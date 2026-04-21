<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Mpesa extends BaseConfig
{
    public string $consumerKey;
    public string $consumerSecret;
    public string $shortcode;
    public string $passkey;
    public string $env;
    public string $callbackUrl;
    public string $authUrl;
    public string $stkUrl;

    public function __construct()
    {
        parent::__construct();

        $this->consumerKey    = env('MPESA_CONSUMER_KEY', '');
        $this->consumerSecret = env('MPESA_CONSUMER_SECRET', '');
        $this->shortcode      = env('MPESA_SHORTCODE', '3428631');
        $this->passkey        = env('MPESA_PASSKEY', '');
        $this->env            = env('MPESA_ENV', 'production');
        $this->callbackUrl    = env('MPESA_CALLBACK_URL', '');

        // API URLs switch automatically based on env
        $base = $this->env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
           

        $this->authUrl = $base . '/oauth/v1/generate?grant_type=client_credentials';
        $this->stkUrl  = $base . '/mpesa/stkpush/v1/processrequest';
    }
}