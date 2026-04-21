<?php
/**
 * @var string $transaction_time
 * @var int $sale_id
 * @var string $employee
 * @var float $discount
 * @var array $cart
 * @var float $subtotal
 * @var array $taxes
 * @var float $total
 * @var array $payments
 * @var float $amount_change
 * @var string $barcode
 * @var array $config
 */
?>

<style>
    #receipt_wrapper {
        font-family: 'Courier New', Courier, monospace;
        width: 100%;
        max-width: 380px;
        margin: 0 auto;
        padding: 10px;
        color: #000;
    }

    /* ── HEADER ── */
    #receipt_header {
        text-align: center;
        margin-bottom: 10px;
    }

    #receipt_header img {
        max-width: 120px;
        margin-bottom: 5px;
    }

    #company_name {
        font-size: 18px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 2px;
    }

    #company_address,
    #company_phone {
        font-size: 11px;
        color: #333;
    }

    #sale_receipt {
        font-size: 13px;
        font-weight: bold;
        text-transform: uppercase;
        margin-top: 6px;
        letter-spacing: 1px;
    }

    #sale_time {
        font-size: 11px;
        color: #555;
    }

    /* ── DIVIDER ── */
    .receipt-divider {
        border: none;
        border-top: 1px dashed #000;
        margin: 8px 0;
    }

    .receipt-divider-solid {
        border: none;
        border-top: 2px solid #000;
        margin: 8px 0;
    }

    /* ── GENERAL INFO ── */
    #receipt_general_info {
        font-size: 11px;
        margin-bottom: 6px;
    }

    #receipt_general_info div {
        margin: 2px 0;
    }

    /* ── ITEMS TABLE ── */
    #receipt_items {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }

    #receipt_items th {
        border-top: 2px solid #000;
        border-bottom: 1px dashed #000;
        padding: 4px 2px;
        text-align: left;
    }

    #receipt_items th.total-value,
    #receipt_items td.total-value {
        text-align: right;
    }

    #receipt_items td {
        padding: 3px 2px;
        vertical-align: top;
    }

    .item-name {
        font-weight: bold;
    }

    .item-unit-price {
        font-size: 10px;
        color: #555;
    }

    .discount {
        color: #c0392b;
        font-size: 10px;
    }

    /* ── TOTALS ── */
    .totals-row td {
        padding: 2px;
        font-size: 11px;
    }

    .grand-total td {
        font-size: 13px;
        font-weight: bold;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        padding: 4px 2px;
    }

    /* ── PAYMENT ── */
    .payment-section {
        margin-top: 6px;
        font-size: 11px;
    }

    .payment-type-badge {
        display: inline-block;
        background: #000;
        color: #fff;
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 10px;
        text-transform: uppercase;
    }

    /* ── FOOTER ── */
    #receipt_footer {
        text-align: center;
        margin-top: 12px;
        font-size: 11px;
    }

    #thank_you_message {
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 4px;
    }

    #served_by {
        font-size: 11px;
        color: #333;
        margin-bottom: 8px;
    }

    #return_policy {
        font-size: 10px;
        color: #555;
        margin-top: 6px;
    }

    #barcode {
        text-align: center;
        margin-top: 10px;
        font-size: 10px;
    }
</style>

<div id="receipt_wrapper" style="font-size: <?= esc($config['receipt_font_size']) ?>px;">

    <!-- ── HEADER ── -->
    <div id="receipt_header">
        <?php if (!empty($config['company_logo'])): ?>
            <div>
                <img src="<?= base_url('uploads/' . esc($config['company_logo'], 'url')) ?>" alt="Logo">
            </div>
        <?php endif; ?>

        <?php if ($config['receipt_show_company_name']): ?>
            <div id="company_name"><?= esc($config['company']) ?></div>
        <?php endif; ?>

        <div id="company_address"><?= nl2br(esc($config['address'])) ?></div>
        <div id="company_phone"><?= esc($config['phone']) ?></div>

        <?php if (!empty($config['tax_id'])): ?>
            <div id="company_tax_id" style="font-size:11px;">
                KRA PIN: <?= esc($config['tax_id']) ?>
            </div>
        <?php endif; ?>

        <hr class="receipt-divider">
        <div id="sale_receipt">*** RECEIPT ***</div>
        <hr class="receipt-divider">
    </div>

    <!-- ── GENERAL INFO ── -->
    <div id="receipt_general_info">
        <div><strong><?= lang('Sales.id') ?>:</strong> <?= esc($sale_id) ?></div>
        <div><strong><?= lang('Sales.date') ?>:</strong> <?= esc($transaction_time) ?></div>

        <?php if (!empty($invoice_number)): ?>
            <div><strong><?= lang('Sales.invoice_number') ?>:</strong> <?= esc($invoice_number) ?></div>
        <?php endif; ?>

        <?php if (isset($customer)): ?>
            <div><strong><?= lang('Customers.customer') ?>:</strong> <?= esc($customer) ?></div>
        <?php endif; ?>

        <div><strong><?= lang('Employees.employee') ?>:</strong> <?= esc($employee) ?></div>
    </div>

    <hr class="receipt-divider">

    <!-- ── ITEMS ── -->
    <table id="receipt_items">
        <thead>
            <tr>
                <th style="width:50%;"><?= lang('Sales.description_abbrv') ?></th>
                <th style="width:15%; text-align:center;"><?= lang('Sales.quantity') ?></th>
                <th style="width:15%; text-align:right;">Unit</th>
                <th style="width:20%;" class="total-value"><?= lang('Sales.total') ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cart as $line => $item): ?>
            <tr>
                <td class="item-name">
                    <?= esc(ucfirst($item['name'])) ?>
                    <?php if (!empty($item['attribute_values'])): ?>
                        <br><span style="font-size:10px; color:#555;"><?= esc($item['attribute_values']) ?></span>
                    <?php endif; ?>
                    <?php if ($config['receipt_show_description'] && !empty($item['description'])): ?>
                        <br><span style="font-size:10px; color:#777;"><?= esc($item['description']) ?></span>
                    <?php endif; ?>
                    <?php if ($config['receipt_show_serialnumber'] && !empty($item['serialnumber'])): ?>
                        <br><span style="font-size:10px;">S/N: <?= esc($item['serialnumber']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($config['receipt_show_tax_indicator']) && !empty($item['tax_category_id'])): ?>
                        <span style="font-size:9px; color:#888;">[V]</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;"><?= to_quantity_decimals($item['quantity']) ?></td>
                <td style="text-align:right;"><?= to_currency($item['unit_price'] ?? ($item['total'] / $item['quantity'])) ?></td>
                <td class="total-value">
                    <?= to_currency($item[$config['receipt_show_total_discount'] ? 'total' : 'discounted_total']) ?>
                </td>
            </tr>

            <?php if ($item['discount'] > 0): ?>
                <tr>
                    <td colspan="3" class="discount">
                        <?php if ($item['discount_type'] == FIXED): ?>
                            <?= lang('Sales.discount') ?>: -<?= to_currency($item['discount']) ?>
                        <?php elseif ($item['discount_type'] == PERCENT): ?>
                            <?= lang('Sales.discount') ?>: -<?= to_decimals($item['discount']) ?>%
                        <?php endif; ?>
                    </td>
                    <td class="total-value discount">-<?= to_currency($item['total'] - $item['discounted_total']) ?></td>
                </tr>
            <?php endif; ?>

        <?php endforeach; ?>
        </tbody>
    </table>

    <hr class="receipt-divider-solid">

    <!-- ── TOTALS ── -->
    <table style="width:100%; font-size:11px;">

        <?php if ($config['receipt_show_total_discount'] && $discount > 0): ?>
            <tr class="totals-row">
                <td><?= lang('Sales.sub_total') ?></td>
                <td style="text-align:right;"><?= to_currency($subtotal) ?></td>
            </tr>
            <tr class="totals-row">
                <td class="discount"><?= lang('Sales.discount') ?></td>
                <td style="text-align:right;" class="discount">-<?= to_currency($discount) ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($config['receipt_show_taxes'] && !empty($taxes)): ?>
            <tr class="totals-row">
                <td><?= lang('Sales.sub_total') ?></td>
                <td style="text-align:right;"><?= to_currency($subtotal) ?></td>
            </tr>
            <?php foreach ($taxes as $tax): ?>
                <tr class="totals-row">
                    <td>
                        <?= esc($tax['tax_group']) ?>
                        (<?= (float)$tax['tax_rate'] ?>% inclusive)
                    </td>
                    <td style="text-align:right;"><?= to_currency_tax($tax['sale_tax_amount']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- GRAND TOTAL -->
        <tr class="grand-total">
            <td><?= lang('Sales.total') ?></td>
            <td style="text-align:right;"><?= to_currency($total) ?></td>
        </tr>
    </table>

    <!-- ── PAYMENTS ── -->
    <div class="payment-section">
        <hr class="receipt-divider">
        <?php
        $only_sale_check      = false;
        $show_giftcard_remainder = false;

        foreach ($payments as $payment_id => $payment):
            $only_sale_check        |= $payment['payment_type'] == lang('Sales.check');
            $splitpayment            = explode(':', $payment['payment_type']);
            $show_giftcard_remainder |= $splitpayment[0] == lang('Sales.giftcard');
        ?>
            <table style="width:100%; font-size:11px; margin-bottom:3px;">
                <tr>
                    <td>
                        <span class="payment-type-badge">
                            <?php
                            // Show friendly payment name
                            $ptype = strtolower($splitpayment[0]);
                            if ($ptype === 'mpesa') {
                                echo '📱 M-Pesa';
                            } elseif (stripos($ptype, 'cash') !== false) {
                                echo '💵 ' . esc($splitpayment[0]);
                            } elseif (stripos($ptype, 'due') !== false) {
                                echo '📋 ' . esc($splitpayment[0]);
                            } else {
                                echo esc($splitpayment[0]);
                            }
                            ?>
                        </span>
                    </td>
                    <td style="text-align:right; font-weight:bold;">
                        <?= to_currency($payment['payment_amount'] * -1) ?>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <?php if (isset($cur_giftcard_value) && $show_giftcard_remainder): ?>
            <table style="width:100%; font-size:11px;">
                <tr>
                    <td><?= lang('Sales.giftcard_balance') ?></td>
                    <td style="text-align:right;"><?= to_currency($cur_giftcard_value) ?></td>
                </tr>
            </table>
        <?php endif; ?>

        <!-- CHANGE / AMOUNT DUE -->
        <table style="width:100%; font-size:12px; font-weight:bold; margin-top:4px;">
            <tr>
                <td>
                    <?= lang($amount_change >= 0
                        ? ($only_sale_check ? 'Sales.check_balance' : 'Sales.change_due')
                        : 'Sales.amount_due') ?>
                </td>
                <td style="text-align:right;"><?= to_currency($amount_change) ?></td>
            </tr>
        </table>
    </div>

    <hr class="receipt-divider">

    <!-- ── FOOTER ── -->
    <div id="receipt_footer">
        <div id="thank_you_message">
            ★ Thank you for your purchase! ★
        </div>
        <div id="served_by">
            You were served by: <strong><?= esc($employee) ?></strong>
        </div>

        <?php if (!empty($config['return_policy'])): ?>
            <div id="return_policy">
                <?= nl2br(esc($config['return_policy'])) ?>
            </div>
        <?php endif; ?>

        <hr class="receipt-divider">

        <!-- BARCODE -->
        <div id="barcode">
            <?= $barcode ?><br>
            <span style="font-size:10px;"><?= esc($sale_id) ?></span>
        </div>

        <div style="font-size:10px; margin-top:8px; color:#777;">
            <?= esc($config['company']) ?> &copy; <?= date('Y') ?>
        </div>
    </div>

</div>


