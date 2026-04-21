<?php
/**
 * Receipt View
 *
 * @var int $sale_id_num
 * @var bool $print_after_sale
 * @var array $config
 * @var string $sale_id
 */

use App\Models\Employee;
?>

<?= view('partial/header') ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-dismissible alert-danger"><?= $error_message ?></div>
    <?= view('partial/footer') ?>
    <?php exit; ?>
<?php endif; ?>

<?php if (!empty($customer_email)): ?>
    <script type="text/javascript">
        $(document).ready(function() {
            var send_email = function() {
                $.get('<?= site_url("sales/sendPdf/{$sale_id_num}/receipt") ?>', function(response) {
                    $.notify({
                        message: response.message
                    }, {
                        type: response.success ? 'success' : 'danger'
                    });
                }, 'json');
            };

            $("#show_email_button").click(send_email);

            <?php if (!empty($email_receipt)): ?>
                send_email();
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<?php
$should_print = $print_after_sale && !session()->get('no_print_once');
session()->remove('no_print_once');
?>

<?= view('partial/print_receipt', [
    'print_after_sale' => $should_print,
    'selected_printer' => 'receipt_printer'
]) ?>

<div class="print_hide" id="control_buttons" style="text-align: right; margin-bottom: 15px;">
    <a href="javascript:printdoc();">
        <div class="btn btn-info btn-sm" id="show_print_button">
            <span class="glyphicon glyphicon-print"></span> <?= lang('Common.print') ?>
        </div>
    </a>
    
    <?php if (!empty($customer_email)): ?>
        <a href="javascript:void(0);">
            <div class="btn btn-info btn-sm" id="show_email_button">
                <span class="glyphicon glyphicon-envelope"></span> <?= lang('Sales.send_receipt') ?>
            </div>
        </a>
    <?php endif; ?>

    <?= anchor('sales', 
        '<span class="glyphicon glyphicon-shopping-cart"></span> ' . lang('Sales.register'), 
        ['class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button']
    ) ?>

    <?php
    $employee = model(Employee::class);
    if ($employee->has_grant('reports_sales', session('person_id'))): ?>
        <?= anchor('sales/manage',
            '<span class="glyphicon glyphicon-list-alt"></span> ' . lang('Sales.takings'),
            ['class' => 'btn btn-info btn-sm', 'id' => 'show_takings_button']
        ) ?>
    <?php endif; ?>
</div>

<!-- ==================== ACTUAL RECEIPT CONTENT ==================== -->
<?= view('sales/' . $config['receipt_template']) ?>

<!-- ==================== AUTO PRINT + RETURN TO REGISTER ==================== -->
<script type="text/javascript">
    <?php if ($should_print): ?>
    var printed = false;

    window.onload = function() {
        if (!printed) {
            printed = true;
            window.print();
        }
    };

    window.onafterprint = function() {
        // Redirect to new sale immediately after print dialog closes
        window.location.replace("<?= site_url('sales') ?>");
    };
    <?php endif; ?>
</script>

<?= view('partial/footer') ?>
