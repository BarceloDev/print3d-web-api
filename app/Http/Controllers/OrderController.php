<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()
            ->orders()
            ->with('client')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($orders);
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
                ->store('orders', 'public');
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
                ->store('orders', 'public');
        }

        $order->update($data);
        $order->load('client');

        return response()->json($order);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'status' => ['required', Rule::in([
                'budget', 'approved', 'printing', 'done', 'delivered',
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
