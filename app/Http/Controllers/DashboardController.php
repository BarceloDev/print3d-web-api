<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard/stats
     *
     * MUDANÇA: endpoint novo. Retorna métricas agregadas do dashboard
     * (contagens por status + receita total) sem enviar nenhuma linha de
     * pedido para o frontend.
     *
     * Antes, o Dashboard.tsx chamava getOrders() e calculava totais no
     * JavaScript — carregando TODOS os pedidos só para somar. Com muitos
     * pedidos entregues isso ficaria lento. Agora o banco faz a agregação
     * e o frontend recebe apenas números.
     *
     * Resposta:
     * {
     *   total_orders: 45,
     *   open_orders: 12,       // todos exceto delivered e rejected
     *   delivered_orders: 28,
     *   rejected_orders: 5,
     *   total_revenue: 15430.00,
     *   by_status: { budget: 2, approved: 3, ... }
     * }
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Uma única query agrupa todos os status de uma vez.
        $counts = Order::where('user_id', $userId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Receita: soma do preço apenas de pedidos entregues.
        $revenue = (float) Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->sum('price');

        $statuses = ['budget', 'approved', 'printing', 'done', 'delivered', 'rejected'];

        $byStatus = [];
        foreach ($statuses as $status) {
            $byStatus[$status] = isset($counts[$status])
                ? (int) $counts[$status]->total
                : 0;
        }

        $total     = array_sum($byStatus);
        $delivered = $byStatus['delivered'];
        $rejected  = $byStatus['rejected'];
        $open      = $total - $delivered - $rejected;

        return response()->json([
            'total_orders'    => $total,
            'open_orders'     => $open,
            'delivered_orders'=> $delivered,
            'rejected_orders' => $rejected,
            'total_revenue'   => $revenue,
            'by_status'       => $byStatus,
        ]);
    }

    /**
     * GET /api/dashboard/charts?range=7d|30d|12m
     */
    public function charts(Request $request): JsonResponse
    {
        $range = $request->query('range', '7d');

        [$labels, $revenueData, $ordersData] = match ($range) {
            '30d'  => $this->last30Days($request),
            '12m'  => $this->last12Months($request),
            default => $this->last7Days($request),
        };

        $funnel = $this->funnelData($request);

        return response()->json([
            'labels'  => $labels,
            'revenue' => $revenueData,
            'orders'  => $ordersData,
            'funnel'  => $funnel,
        ]);
    }

    private function last7Days(Request $request): array
    {
        $userId = $request->user()->id;
        $today  = Carbon::today();

        $days = collect(range(6, 0))->map(fn ($i) => $today->copy()->subDays($i));

        $rows = Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [
                $days->first()->copy()->startOfDay(),
                $today->copy()->endOfDay(),
            ])
            ->select(
                DB::raw("DATE(delivered_at) as day"),
                DB::raw("SUM(price) as total_revenue"),
                DB::raw("COUNT(*) as total_orders"),
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $ptDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $labels  = [];
        $revenue = [];
        $orders  = [];

        foreach ($days as $day) {
            $key       = $day->toDateString();
            $labels[]  = $ptDays[$day->dayOfWeek];
            $revenue[] = isset($rows[$key]) ? (float) $rows[$key]->total_revenue : 0;
            $orders[]  = isset($rows[$key]) ? (int)   $rows[$key]->total_orders  : 0;
        }

        return [$labels, $revenue, $orders];
    }

    private function last30Days(Request $request): array
    {
        $userId = $request->user()->id;
        $today  = Carbon::today();

        $days = collect(range(29, 0))->map(fn ($i) => $today->copy()->subDays($i));

        $rows = Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [
                $days->first()->copy()->startOfDay(),
                $today->copy()->endOfDay(),
            ])
            ->select(
                DB::raw("DATE(delivered_at) as day"),
                DB::raw("SUM(price) as total_revenue"),
                DB::raw("COUNT(*) as total_orders"),
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels  = [];
        $revenue = [];
        $orders  = [];

        foreach ($days as $day) {
            $key       = $day->toDateString();
            $labels[]  = $day->format('d/m');
            $revenue[] = isset($rows[$key]) ? (float) $rows[$key]->total_revenue : 0;
            $orders[]  = isset($rows[$key]) ? (int)   $rows[$key]->total_orders  : 0;
        }

        return [$labels, $revenue, $orders];
    }

    private function last12Months(Request $request): array
    {
        $userId    = $request->user()->id;
        $thisMonth = Carbon::today()->startOfMonth();

        $months = collect(range(11, 0))->map(fn ($i) => $thisMonth->copy()->subMonths($i));

        $rows = Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [
                $months->first()->copy()->startOfMonth(),
                $thisMonth->copy()->endOfMonth(),
            ])
            ->select(
                DB::raw("DATE_FORMAT(delivered_at, '%Y-%m') as month"),
                DB::raw("SUM(price) as total_revenue"),
                DB::raw("COUNT(*) as total_orders"),
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $ptMonths = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun',
                     'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $labels  = [];
        $revenue = [];
        $orders  = [];

        foreach ($months as $month) {
            $key       = $month->format('Y-m');
            $labels[]  = $ptMonths[$month->month - 1] . '/' . $month->format('y');
            $revenue[] = isset($rows[$key]) ? (float) $rows[$key]->total_revenue : 0;
            $orders[]  = isset($rows[$key]) ? (int)   $rows[$key]->total_orders  : 0;
        }

        return [$labels, $revenue, $orders];
    }

    private function funnelData(Request $request): array
    {
        $userId = $request->user()->id;

        $rows = Order::where('user_id', $userId)
            ->select('status', DB::raw("COUNT(*) as total"))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statuses = [
            'budget'    => 'Orçamento',
            'approved'  => 'Aprovado',
            'printing'  => 'Imprimindo',
            'done'      => 'Pronto',
            'delivered' => 'Entregue',
            'rejected'  => 'Rejeitado',
        ];

        $result = [];
        foreach ($statuses as $key => $label) {
            $count = isset($rows[$key]) ? (int) $rows[$key]->total : 0;
            if ($count > 0) {
                $result[] = ['label' => $label, 'value' => $count];
            }
        }

        return $result;
    }
}
