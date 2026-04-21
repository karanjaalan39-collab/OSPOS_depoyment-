<?php

namespace App\Controllers;

require_once('Secure_Controller.php');

class Best_sellers extends Secure_Controller
{
    private array $config;

    public function __construct()
    {
        parent::__construct('reports');
        $this->config = config(\Config\OSPOS::class)->settings;
    }

    public function getIndex(): void
    {
        // Date range defaults
        $start_date = $this->request->getGet('start_date') ?? date('Y-m-01');
        $end_date   = $this->request->getGet('end_date')   ?? date('Y-m-d');
        $category   = $this->request->getGet('category')   ?? '';
        $limit      = (int)($this->request->getGet('limit') ?? 20);
        $sort_by    = $this->request->getGet('sort_by')    ?? 'qty';

        $db = \Config\Database::connect();

        // Build query
        $builder = $db->table('ospos_sales_items si')
            ->select('
                i.item_id,
                i.name AS item_name,
                i.category,
                SUM(si.quantity_purchased) AS total_qty,
                SUM(si.quantity_purchased * si.item_unit_price) AS total_revenue,
                SUM(si.quantity_purchased * si.item_cost_price) AS total_cost,
                SUM(si.quantity_purchased * (si.item_unit_price - si.item_cost_price)) AS total_profit,
                COUNT(DISTINCT si.sale_id) AS num_transactions
            ')
            ->join('ospos_items i', 'i.item_id = si.item_id')
            ->join('ospos_sales s', 's.sale_id = si.sale_id')
            ->where('s.sale_status', 0)  // COMPLETED sales only
            ->where('DATE(s.sale_time) >=', $start_date)
            ->where('DATE(s.sale_time) <=', $end_date)
            ->where('i.deleted', 0)
            ->groupBy('si.item_id');

        if (!empty($category)) {
            $builder->where('i.category', $category);
        }

        // Sort
        if ($sort_by === 'revenue') {
            $builder->orderBy('total_revenue', 'DESC');
        } elseif ($sort_by === 'profit') {
            $builder->orderBy('total_profit', 'DESC');
        } else {
            $builder->orderBy('total_qty', 'DESC');
        }

        $builder->limit($limit);
        $results = $builder->get()->getResultArray();

        // Grand totals
        $grand_qty     = array_sum(array_column($results, 'total_qty'));
        $grand_revenue = array_sum(array_column($results, 'total_revenue'));
        $grand_profit  = array_sum(array_column($results, 'total_profit'));

        // Add percentage column
        foreach ($results as &$row) {
            $row['pct_revenue'] = $grand_revenue > 0
                ? round(($row['total_revenue'] / $grand_revenue) * 100, 1)
                : 0;
            $row['pct_qty'] = $grand_qty > 0
                ? round(($row['total_qty'] / $grand_qty) * 100, 1)
                : 0;
            $row['margin'] = $row['total_revenue'] > 0
                ? round(($row['total_profit'] / $row['total_revenue']) * 100, 1)
                : 0;
        }
        unset($row);

        // Get categories for filter dropdown
        $cat_rows   = $db->table('ospos_items')
            ->select('category')
            ->distinct()
            ->where('category !=', '')
            ->where('deleted', 0)
            ->orderBy('category', 'ASC')
            ->get()->getResultArray();
        $categories = array_column($cat_rows, 'category');

        // Chart data (top 10)
        $chart_labels  = array_map(fn($r) => $r['item_name'], array_slice($results, 0, 10));
        $chart_qty     = array_map(fn($r) => (float)$r['total_qty'], array_slice($results, 0, 10));
        $chart_revenue = array_map(fn($r) => (float)$r['total_revenue'], array_slice($results, 0, 10));

        $data = [
            'results'       => $results,
            'start_date'    => $start_date,
            'end_date'      => $end_date,
            'category'      => $category,
            'categories'    => $categories,
            'limit'         => $limit,
            'sort_by'       => $sort_by,
            'grand_qty'     => $grand_qty,
            'grand_revenue' => $grand_revenue,
            'grand_profit'  => $grand_profit,
            'chart_labels'  => json_encode($chart_labels),
            'chart_qty'     => json_encode($chart_qty),
            'chart_revenue' => json_encode($chart_revenue),
        ];

        echo view('reports/best_sellers', $data);
    }
}
