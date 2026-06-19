<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     *
     * Parâmetros opcionais:
     *  - status    : filtra por status (usado pelo Kanban por coluna)
     *  - per_page  : ativa paginação e define quantos itens por página
     *  - page      : número da página (default 1, relevante só com per_page)
     *
     * MUDANÇA: antes retornava sempre todos os pedidos em um array plano.
     * Agora suporta dois modos:
     *   • Sem per_page → comportamento original (array plano, compatibilidade)
     *   • Com per_page → retorna objeto paginado do Laravel:
     *     { current_page, data, last_page, per_page, total, ... }
     *
     * O Kanban chama GET /orders?status=printing&per_page=5&page=1
     * para carregar 5 pedidos por coluna.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()
            ->orders()
            ->with('client')
            ->orderByDesc('created_at');

        // MUDANÇA: filtro por status adicionado (necessário para carregar
        // cada coluna do Kanban de forma independente).
        if ($request->filled('status')) {
            $request->validate([
                'status' => Rule::in([
                    'budget', 'approved', 'printing', 'done', 'delivered', 'rejected',
                ]),
            ]);
            $query->where('status', $request->status);
        }

        // MUDANÇA: paginação adicionada. Sem per_page retorna array plano
        // (mantém compatibilidade com chamadas legadas — ex: getRecentOrders).
        $perPage = (int) $request->get('per_page', 0);
        if ($perPage > 0) {
            $perPage = min($perPage, 100); // cap de segurança
            return response()->json($query->paginate($perPage));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'       => 'required|integer|exists:clients,id',
            'title'           => 'required|string|max:255',
            'description'     => 'required|string',
            'price'           => 'required|numeric|min:0',
            'deadline'        => 'required|date',
            'reference_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('reference_image')) {
            $data['reference_image'] = $request
                ->file('reference_image')
                ->store('orders', 's3');
        }

        $data['user_id'] = $request->user()->id;
        $order = Order::create($data);
        $order->load('client');

        return response()->json($order, 201);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        $order->load('client');

        return response()->json($order);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'client_id'       => 'sometimes|integer|exists:clients,id',
            'title'           => 'sometimes|string|max:255',
            'description'     => 'sometimes|string',
            'price'           => 'sometimes|numeric|min:0',
            'deadline'        => 'sometimes|date',
            'reference_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('reference_image')) {
            $data['reference_image'] = $request
                ->file('reference_image')
                ->store('orders', 's3');
        }

        $order->update($data);
        $order->load('client');

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        // 'approved' é INTENCIONALMENTE excluído da lista.
        // Somente o cliente pode aprovar um orçamento, via link público
        // (PublicOrderController::approve) — o vendedor não tem permissão.
        $data = $request->validate([
            'status' => ['required', Rule::in([
                'budget', 'printing', 'done', 'delivered', 'rejected',
            ])],
        ]);

        $order->update($data);

        return response()->json($order);
    }

    public function destroy(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);
        $order->delete();

        return response()->json(null, 204);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
