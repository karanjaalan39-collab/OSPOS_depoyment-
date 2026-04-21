<?php
/**
 * Best Sellers Report View
 * Place in: app/Views/reports/best_sellers.php
 */
?>
<div class="container-fluid" style="padding: 15px;">

    <!-- Page Title -->
    <div class="row">
        <div class="col-xs-12">
            <h3 style="margin-bottom: 5px;">
                <span class="glyphicon glyphicon-fire" style="color:#e74c3c;"></span>
                Best Sellers Report — ZETECH SUPERMARKET
            </h3>
            <p class="text-muted" style="margin-top:0;">
                Top selling items ranked by quantity, revenue or profit
            </p>
            <hr style="margin-top:5px;">
        </div>
    </div>

    <!-- Filters -->
    <form method="get" action="<?= base_url('best_sellers') ?>" class="form-inline" style="margin-bottom:15px;">
        <div class="form-group" style="margin-right:8px;">
            <label style="margin-right:4px;">From:</label>
            <input type="date" name="start_date" class="form-control input-sm" value="<?= esc($start_date) ?>">
        </div>
        <div class="form-group" style="margin-right:8px;">
            <label style="margin-right:4px;">To:</label>
            <input type="date" name="end_date" class="form-control input-sm" value="<?= esc($end_date) ?>">
        </div>
        <div class="form-group" style="margin-right:8px;">
            <label style="margin-right:4px;">Category:</label>
            <select name="category" class="form-control input-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= esc($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                        <?= esc($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-right:8px;">
            <label style="margin-right:4px;">Sort by:</label>
            <select name="sort_by" class="form-control input-sm">
                <option value="qty"     <?= $sort_by === 'qty'     ? 'selected' : '' ?>>Quantity Sold</option>
                <option value="revenue" <?= $sort_by === 'revenue' ? 'selected' : '' ?>>Revenue</option>
                <option value="profit"  <?= $sort_by === 'profit'  ? 'selected' : '' ?>>Profit</option>
            </select>
        </div>
        <div class="form-group" style="margin-right:8px;">
            <label style="margin-right:4px;">Show top:</label>
            <select name="limit" class="form-control input-sm">
                <option value="10"  <?= $limit == 10  ? 'selected' : '' ?>>10 items</option>
                <option value="20"  <?= $limit == 20  ? 'selected' : '' ?>>20 items</option>
                <option value="50"  <?= $limit == 50  ? 'selected' : '' ?>>50 items</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 items</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">
            <span class="glyphicon glyphicon-search"></span> Generate
        </button>
        <button type="button" class="btn btn-default btn-sm" onclick="window.print();" style="margin-left:5px;">
            <span class="glyphicon glyphicon-print"></span> Print
        </button>
    </form>

    <!-- Quick Date Shortcuts -->
    <div style="margin-bottom:15px;">
        <strong>Quick:</strong>
        <?php
        $shortcuts = [
            'Today'       => [date('Y-m-d'), date('Y-m-d')],
            'This Week'   => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
            'This Month'  => [date('Y-m-01'), date('Y-m-d')],
            'Last Month'  => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month'))],
            'This Year'   => [date('Y-01-01'), date('Y-m-d')],
        ];
        foreach ($shortcuts as $label => [$s, $e]): ?>
            <a href="<?= base_url("best_sellers?start_date=$s&end_date=$e&category=" . urlencode($category) . "&sort_by=$sort_by&limit=$limit") ?>"
               class="btn btn-xs btn-default" style="margin-right:3px;">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert alert-info">
            <span class="glyphicon glyphicon-info-sign"></span>
            No sales data found for the selected period.
        </div>
    <?php else: ?>

    <!-- Summary Cards -->
    <div class="row" style="margin-bottom:20px;">
        <div class="col-xs-4">
            <div style="background:#3498db;color:#fff;border-radius:6px;padding:15px;text-align:center;">
                <div style="font-size:24px;font-weight:bold;">
                    <?= number_format($grand_qty, 0) ?>
                </div>
                <div style="font-size:13px;opacity:0.9;">Total Units Sold</div>
            </div>
        </div>
        <div class="col-xs-4">
            <div style="background:#27ae60;color:#fff;border-radius:6px;padding:15px;text-align:center;">
                <div style="font-size:24px;font-weight:bold;">
                    KES <?= number_format($grand_revenue, 2) ?>
                </div>
                <div style="font-size:13px;opacity:0.9;">Total Revenue</div>
            </div>
        </div>
        <div class="col-xs-4">
            <div style="background:#e67e22;color:#fff;border-radius:6px;padding:15px;text-align:center;">
                <div style="font-size:24px;font-weight:bold;">
                    KES <?= number_format($grand_profit, 2) ?>
                </div>
                <div style="font-size:13px;opacity:0.9;">Total Profit</div>
            </div>
        </div>
    </div>

    <!-- Bar Chart -->
    <div class="row" style="margin-bottom:20px;" id="chart-section">
        <div class="col-xs-12">
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:15px;">
                <h4 style="margin-top:0;">
                    Top 10 Items —
                    <?= $sort_by === 'revenue' ? 'By Revenue (KES)' : ($sort_by === 'profit' ? 'By Profit (KES)' : 'By Quantity Sold') ?>
                </h4>
                <canvas id="bestSellersChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-xs-12">
            <div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:15px;">
                <h4 style="margin-top:0;">
                    Detailed Breakdown
                    <small class="text-muted">
                        <?= esc($start_date) ?> to <?= esc($end_date) ?>
                        <?= !empty($category) ? ' — ' . esc($category) : '' ?>
                    </small>
                </h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-condensed" style="font-size:13px;">
                        <thead style="background:#2c3e50;color:#fff;">
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th class="text-right">Qty Sold</th>
                                <th class="text-right">% of Qty</th>
                                <th class="text-right">Revenue (KES)</th>
                                <th class="text-right">% of Revenue</th>
                                <th class="text-right">Profit (KES)</th>
                                <th class="text-right">Margin %</th>
                                <th class="text-right">Transactions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $i => $row): ?>
                            <tr>
                                <td>
                                    <?php if ($i < 3): ?>
                                        <span style="font-size:16px;">
                                            <?= ['🥇','🥈','🥉'][$i] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted"><?= $i + 1 ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= esc($row['item_name']) ?></strong></td>
                                <td>
                                    <span style="background:#ecf0f1;padding:2px 6px;border-radius:3px;font-size:11px;">
                                        <?= esc($row['category']) ?: '<em class="text-muted">Uncategorized</em>' ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <strong><?= number_format($row['total_qty'], 0) ?></strong>
                                </td>
                                <td class="text-right">
                                    <!-- Progress bar for qty % -->
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px;">
                                        <div style="width:60px;background:#ecf0f1;border-radius:3px;height:8px;">
                                            <div style="width:<?= $row['pct_qty'] ?>%;background:#3498db;height:8px;border-radius:3px;"></div>
                                        </div>
                                        <span><?= $row['pct_qty'] ?>%</span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    KES <?= number_format($row['total_revenue'], 2) ?>
                                </td>
                                <td class="text-right">
                                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:5px;">
                                        <div style="width:60px;background:#ecf0f1;border-radius:3px;height:8px;">
                                            <div style="width:<?= $row['pct_revenue'] ?>%;background:#27ae60;height:8px;border-radius:3px;"></div>
                                        </div>
                                        <span><?= $row['pct_revenue'] ?>%</span>
                                    </div>
                                </td>
                                <td class="text-right" style="color:<?= $row['total_profit'] >= 0 ? '#27ae60' : '#e74c3c' ?>">
                                    KES <?= number_format($row['total_profit'], 2) ?>
                                </td>
                                <td class="text-right">
                                    <span style="
                                        color: <?= $row['margin'] >= 20 ? '#27ae60' : ($row['margin'] >= 10 ? '#e67e22' : '#e74c3c') ?>;
                                        font-weight:bold;
                                    ">
                                        <?= $row['margin'] ?>%
                                    </span>
                                </td>
                                <td class="text-right text-muted">
                                    <?= number_format($row['num_transactions']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background:#f8f9fa;font-weight:bold;">
                            <tr>
                                <td colspan="3"><strong>TOTALS</strong></td>
                                <td class="text-right"><?= number_format($grand_qty, 0) ?></td>
                                <td class="text-right">100%</td>
                                <td class="text-right">KES <?= number_format($grand_revenue, 2) ?></td>
                                <td class="text-right">100%</td>
                                <td class="text-right" style="color:#27ae60;">
                                    KES <?= number_format($grand_profit, 2) ?>
                                </td>
                                <td class="text-right">
                                    <?= $grand_revenue > 0 ? round(($grand_profit / $grand_revenue) * 100, 1) : 0 ?>%
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
<?php if (!empty($results)): ?>
var ctx = document.getElementById('bestSellersChart').getContext('2d');
var sortBy = '<?= $sort_by ?>';

var chartData = sortBy === 'revenue' || sortBy === 'profit'
    ? <?= $chart_revenue ?>
    : <?= $chart_qty ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chart_labels ?>,
        datasets: [{
            label: sortBy === 'revenue' ? 'Revenue (KES)' : (sortBy === 'profit' ? 'Profit (KES)' : 'Qty Sold'),
            data: chartData,
            backgroundColor: [
                '#e74c3c','#e67e22','#f1c40f',
                '#2ecc71','#3498db','#9b59b6',
                '#1abc9c','#e91e63','#ff5722','#607d8b'
            ],
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        var val = ctx.raw;
                        if (sortBy === 'revenue' || sortBy === 'profit') {
                            return 'KES ' + val.toLocaleString('en-KE', {minimumFractionDigits: 2});
                        }
                        return val.toLocaleString() + ' units';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(val) {
                        if (sortBy === 'revenue' || sortBy === 'profit') {
                            return 'KES ' + val.toLocaleString();
                        }
                        return val.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<!-- Print styles -->
<style>
@media print {
    #chart-section, form, .btn { display: none !important; }
    .container-fluid { padding: 0 !important; }
}
</style>
