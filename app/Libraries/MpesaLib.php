<?php

namespace App\Libraries;

use Config\Mpesa;

class MpesaLib
{
    protected Mpesa $config;

    public function __construct()
    {
        $this->config = new Mpesa();
    }

    // ── 1. GET ACCESS TOKEN ──────────────────────────────────────
    public function getAccessToken(): ?string
    {
        $credentials = base64_encode(
            $this->config->consumerKey . ':' . $this->config->consumerSecret
        );

        $ch = curl_init($this->config->authUrl);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . $credentials],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,  // localhost only
        ]);

        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        return $response->access_token ?? null;
    }

    // ── 2. SEND STK PUSH ─────────────────────────────────────────
    public function stkPush(string $phone, int $amount, string $reference = 'OSPOS'): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Could not get access token. Check Consumer Key/Secret.'];
        }

        $phone     = $this->formatPhone($phone);
        $timestamp = date('YmdHis');
        $password  = base64_encode($this->config->shortcode . $this->config->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->config->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $this->config->shortcode,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $this->config->callbackUrl,
            'AccountReference'  => $reference,
            'TransactionDesc'   => 'Payment via OSPOS',
        ];

        $ch = curl_init($this->config->stkUrl);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
            return [
                'success'           => true,
                'CheckoutRequestID' => $result['CheckoutRequestID'],
                'message'           => 'STK Push sent. Customer should see prompt on phone.',
            ];
        }

        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK Push failed',
        ];
    }

    // ── 3. HANDLE CALLBACK FROM SAFARICOM ────────────────────────
    public function processCallback(array $data): array
    {
        $callback = $data['Body']['stkCallback'] ?? [];

        if (empty($callback)) {
            return ['success' => false, 'message' => 'Invalid callback data'];
        }

        $resultCode = $callback['ResultCode'];

        if ($resultCode !== 0) {
            return [
                'success' => false,
                'code'    => $resultCode,
                'message' => $callback['ResultDesc'],
            ];
        }

        // Extract payment details
        $items  = $callback['CallbackMetadata']['Item'];
        $parsed = [];
        foreach ($items as $item) {
            $parsed[$item['Name']] = $item['Value'] ?? null;
        }

        return [
            'success'       => true,
            'amount'        => $parsed['Amount'],
            'receipt'       => $parsed['MpesaReceiptNumber'],
            'phone'         => $parsed['PhoneNumber'],
            'transaction_date' => $parsed['TransactionDate'],
        ];
    }

    // ── HELPER: Format phone to 254XXXXXXXXX ─────────────────────
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone); // remove non-digits

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }
}