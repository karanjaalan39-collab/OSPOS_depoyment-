<?php
/**
 * @var string $selected_printer
 * @var bool $print_after_sale
 * @var array $config
 */
?>

<script type="text/javascript">

let alreadyPrinted = false;

function printdoc() {
    if (alreadyPrinted) return;

    alreadyPrinted = true;

    if (window.jsPrintSetup) {

        jsPrintSetup.setOption('marginTop', '<?= $config['print_top_margin'] ?>');
        jsPrintSetup.setOption('marginLeft', '<?= $config['print_left_margin'] ?>');
        jsPrintSetup.setOption('marginBottom', '<?= $config['print_bottom_margin'] ?>');
        jsPrintSetup.setOption('marginRight', '<?= $config['print_right_margin'] ?>');

        <?php if (!$config['print_header']) { ?>
            jsPrintSetup.setOption('headerStrLeft', '');
            jsPrintSetup.setOption('headerStrCenter', '');
            jsPrintSetup.setOption('headerStrRight', '');
        <?php } ?>

        <?php if (!$config['print_footer']) { ?>
            jsPrintSetup.setOption('footerStrLeft', '');
            jsPrintSetup.setOption('footerStrCenter', '');
            jsPrintSetup.setOption('footerStrRight', '');
        <?php } ?>

        var printers = jsPrintSetup.getPrintersList().split(',');

        for (var index in printers) {
            var default_ticket_printer = window.localStorage && localStorage['<?= esc($selected_printer, 'js') ?>'];
            var selected_printer = printers[index];

            if (selected_printer == default_ticket_printer) {

                jsPrintSetup.setPrinter(selected_printer);
                jsPrintSetup.clearSilentPrint();

                <?php if (!$config['print_silently']) { ?>
                    jsPrintSetup.setOption('printSilent', 1);
                <?php } ?>

                jsPrintSetup.print();
                break;
            }
        }

    } else {
        window.print();
    }

    // Safe redirect AFTER print
   window.onafterprint = function () {
    window.location.href = "<?= site_url('sales') ?>";
};

}

<?php if ($print_after_sale && !session()->get('no_print_once')) { ?>
    window.onload = function () {
        printdoc();
    };
<?php } ?>

</script>
