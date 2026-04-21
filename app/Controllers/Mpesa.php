<?php

namespace App\Controllers;

use App\Libraries\MpesaLib;
use CodeIgniter\Controller;

class Mpesa extends Controller
{
    protected MpesaLib $mpesa;

    public function __construct()
    {
        $this->mpesa = new MpesaLib();
    }

    // ── INITIATE STK PUSH ─────────────────────────────────────────
    public function postPay()
    {
        $phone     = $this->request->getPost('phone');
        $amount    = $this->request->getPost('amount');
        $reference = $this->request->getPost('reference') ?? 'OSPOS';

        if (!$phone || !$amount) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Phone and amount are required'
            ]);
        }

        $result = $this->mpesa->stkPush($phone, (int)$amount, $reference);
        return $this->response->setJSON($result);
    }

    // ── CALLBACK: Safaricom posts result here ─────────────────────
    public function postCallback()
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);

        log_message('info', 'MPESA CALLBACK RAW: ' . $raw);

        $callback   = $data['Body']['stkCallback'] ?? [];
        $resultCode = $callback['ResultCode'] ?? -1;

        if ($resultCode == 0) {
            $items   = $callback['CallbackMetadata']['Item'] ?? [];
            $receipt = null;
            $amount  = null;
            $phone   = null;

            foreach ($items as $item) {
                switch ($item['Name']) {
                    case 'MpesaReceiptNumber': $receipt = $item['Value']; break;
                    case 'Amount':             $amount  = $item['Value']; break;
                    case 'PhoneNumber':        $phone   = $item['Value']; break;
                }
            }

            log_message('info', "MPESA EXTRACTED - Receipt:$receipt Amount:$amount Phone:$phone");

            if ($receipt && $amount && $phone) {
                $cacheData = [
                    'receipt' => $receipt,
                    'amount'  => $amount,
                    'phone'   => $phone,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'status'  => 'paid'
                ];

                $cacheFile = WRITEPATH . 'cache/mpesa_' . $receipt . '.json';
                $saved     = file_put_contents($cacheFile, json_encode($cacheData));

                if ($saved !== false) {
                    log_message('info', 'MPESA Cache saved: ' . $cacheFile);
                } else {
                    log_message('error', 'MPESA Cache FAILED: ' . $cacheFile);
                }
            }
        } else {
            log_message('error', 'MPESA FAILED ResultCode: ' . $resultCode);
        }

        return $this->response->setStatusCode(200)->setJSON(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    // ── POLL: POS checks if payment arrived ───────────────────────
    public function getCheckPayment()
    {
        $phone = $this->request->getGet('phone');

        if (!$phone) {
            return $this->response->setJSON(['paid' => false, 'message' => 'No phone provided']);
        }

        $files = glob(WRITEPATH . 'cache/mpesa_*.json');

        foreach ($files as $file) {
            $content    = json_decode(file_get_contents($file), true);
            $filePhone  = substr(preg_replace('/\D/', '', $content['phone'] ?? ''), -9);
            $checkPhone = substr(preg_replace('/\D/', '', $phone), -9);

            if ($filePhone === $checkPhone) {
                $paidAt = strtotime($content['paid_at']);
                if ((time() - $paidAt) < 600) {
                    unlink($file);
                    return $this->response->setJSON([
                        'paid'    => true,
                        'receipt' => $content['receipt'],
                        'amount'  => $content['amount'],
                        'phone'   => $content['phone'],
                    ]);
                }
            }
        }

        return $this->response->setJSON(['paid' => false]);
    }

    // ── RAW TEST: Simulate callback ───────────────────────────────
    public function getRawtest()
    {
        $items = [
            ['Name' => 'Amount',             'Value' => 1],
            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TESTRECEIPT01'],
            ['Name' => 'Balance'],
            ['Name' => 'TransactionDate',    'Value' => 20260311111604],
            ['Name' => 'PhoneNumber',        'Value' => 254116221270],
        ];

        $receipt = null;
        $amount  = null;
        $phone   = null;

        foreach ($items as $item) {
            switch ($item['Name']) {
                case 'MpesaReceiptNumber': $receipt = $item['Value']; break;
                case 'Amount':             $amount  = $item['Value']; break;
                case 'PhoneNumber':        $phone   = $item['Value']; break;
            }
        }

        echo "Receipt: $receipt<br>";
        echo "Amount: $amount<br>";
        echo "Phone: $phone<br>";

        $cacheData = [
            'receipt' => $receipt,
            'amount'  => $amount,
            'phone'   => $phone,
            'paid_at' => date('Y-m-d H:i:s'),
            'status'  => 'paid'
        ];

        $cacheFile = WRITEPATH . 'cache/mpesa_' . $receipt . '.json';
        $saved     = file_put_contents($cacheFile, json_encode($cacheData));

        if ($saved !== false) {
            echo '<br>✅ Cache saved: ' . $cacheFile;
            echo '<br><br><a href="' . site_url('mpesa/checkcache') . '">Check Cache</a>';
        } else {
            echo '<br>❌ Failed to save cache file';
        }
    }

    // ── WRITE TEST ────────────────────────────────────────────────
    public function getWritetest()
    {
        $file   = WRITEPATH . 'cache/test_write.json';
        $result = file_put_contents($file, json_encode(['test' => 'ok', 'time' => date('Y-m-d H:i:s')]));

        if ($result !== false) {
            echo '✅ Write SUCCESS: ' . $file;
        } else {
            echo '❌ Write FAILED<br>';
            echo 'WRITEPATH = ' . WRITEPATH . '<br>';
            echo 'Cache exists: '   . (is_dir(WRITEPATH . 'cache/') ? 'YES' : 'NO') . '<br>';
            echo 'Cache writable: ' . (is_writable(WRITEPATH . 'cache/') ? 'YES' : 'NO');
        }
    }

    // ── TEST PAGE ─────────────────────────────────────────────────
    public function getTest()
    {
        $token = $this->mpesa->getAccessToken();

        echo '<style>
            body { font-family: Arial; padding: 30px; max-width: 500px; }
            input { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:4px; }
            button { background:#27ae60; color:white; padding:12px 30px; border:none; border-radius:4px; cursor:pointer; width:100%; font-size:16px; }
            .ok   { background:#d4edda; color:#155724; padding:10px; border-radius:4px; margin-bottom:10px; }
            .fail { background:#f8d7da; color:#721c24; padding:10px; border-radius:4px; margin-bottom:10px; }
        </style>';

        if ($token) {
            echo '<div class="ok">✅ Access Token OK</div>';
        } else {
            echo '<div class="fail">❌ Token Failed — Check credentials</div>';
            return;
        }

        echo '
        <h2>M-Pesa STK Push Test</h2>
        <div id="result"></div>
        <label>Phone</label>
        <input type="text" id="phone" value="254708374149"/>
        <label>Amount (KES)</label>
        <input type="number" id="amount" value="1"/>
        <label>Reference</label>
        <input type="text" id="reference" value="TEST001"/>
        <br><br>
        <button onclick="sendPush()">Send STK Push</button>
        <script>
        function sendPush() {
            const resultDiv = document.getElementById("result");
            resultDiv.innerHTML = "<p>⏳ Sending...</p>";
            fetch("' . site_url('mpesa/pay') . '", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "phone=" + document.getElementById("phone").value +
                      "&amount=" + document.getElementById("amount").value +
                      "&reference=" + document.getElementById("reference").value
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = "<div class=\'ok\'>✅ STK Push Sent! ID: " + data.CheckoutRequestID + "</div>";
                } else {
                    resultDiv.innerHTML = "<div class=\'fail\'>❌ " + data.message + "</div>";
                }
            });
        }
        </script>';
    }

    // ── VIEW LOGS ─────────────────────────────────────────────────
    public function getLogs()
    {
        $files = glob(WRITEPATH . 'logs/*.log');

        if (empty($files)) {
            echo '<h3>No log files found</h3>';
            return;
        }

        $latest = end($files);

        echo '<style>
            body { font-family:monospace; padding:20px; background:#1e1e1e; color:#d4d4d4; }
            h2 { color:#4ec9b0; }
            .mpesa { color:#ce9178; background:#2d2d2d; padding:5px; margin:5px 0; display:block; }
            .error { color:#f44747; }
            pre { white-space:pre-wrap; word-wrap:break-word; }
        </style>';

        echo '<h2>📋 ' . basename($latest) . '</h2>';
        echo '<a href="' . site_url('mpesa/clearcache') . '" style="color:yellow;">Clear Cache</a> | ';
        echo '<a href="' . site_url('mpesa/checkcache') . '" style="color:lightgreen;">Check Cache</a>';
        echo '<hr><pre>';

        foreach (explode("\n", file_get_contents($latest)) as $line) {
            if (stripos($line, 'mpesa') !== false) {
                echo '<span class="mpesa">' . esc($line) . '</span>';
            } elseif (stripos($line, 'error') !== false) {
                echo '<span class="error">' . esc($line) . '</span>';
            } else {
                echo esc($line) . "\n";
            }
        }
        echo '</pre>';
    }

    // ── CHECK CACHE ───────────────────────────────────────────────
    public function getCheckcache()
    {
        $files = glob(WRITEPATH . 'cache/mpesa_*.json');

        echo '<style>body { font-family:monospace; padding:20px; }</style>';
        echo '<h2>M-Pesa Cache Files</h2>';

        if (empty($files)) {
            echo '<p style="color:red;">❌ No M-Pesa cache files found.</p>';
            echo '<p>Callback has NOT been received yet.</p>';
        } else {
            echo '<p style="color:green;">✅ Found ' . count($files) . ' cache file(s):</p>';
            foreach ($files as $file) {
                $content = json_decode(file_get_contents($file), true);
                echo '<pre style="background:#f0f0f0; padding:10px;">';
                echo 'Receipt: ' . ($content['receipt'] ?? 'N/A') . "\n";
                echo 'Amount:  KES ' . ($content['amount'] ?? 'N/A') . "\n";
                echo 'Phone:   ' . ($content['phone'] ?? 'N/A') . "\n";
                echo 'Paid At: ' . ($content['paid_at'] ?? 'N/A') . "\n";
                echo '</pre>';
            }
        }

        echo '<br><a href="' . site_url('mpesa/logs') . '">View Logs</a>';
    }

    // ── CLEAR CACHE ───────────────────────────────────────────────
    public function getClearcache()
    {
        $files = glob(WRITEPATH . 'cache/mpesa_*.json');
        foreach ($files as $file) {
            unlink($file);
        }
        echo '<p style="color:green;">✅ Cleared ' . count($files) . ' file(s).</p>';
        echo '<a href="' . site_url('mpesa/logs') . '">Back to Logs</a>';
    }
}

