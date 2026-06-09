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
                // BUG CORRIGIDO: copy() antes de startOfDay/endOfDay
                // para não mutar o objeto Carbon original
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
                // BUG CORRIGIDO: mesmo problema de mutação
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
                // BUG CORRIGIDO: copy() para não mutar $months->first()
                $months->first()->copy()->startOfMonth(),
                $thisMonth->copy()->endOfMonth(),
            ])
            ->select(
                DB::raw("TO_CHAR(delivered_at, 'YYYY-MM') as month"),
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
