<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;

class PublicOrderController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $order = Order::with('client')
            ->where('public_token', $token)
            ->firstOrFail();

        return response()->json($order);
    }

    public function approve(string $token): JsonResponse
    {
        $order = Order::where('public_token', $token)
            ->where('status', 'budget')
            ->firstOrFail();

        $order->update(['status' => 'approved']);

        return response()->json($order);
    }

    public function reject(string $token): JsonResponse
    {
        $order = Order::where('public_token', $token)
            ->where('status', 'budget')
            ->firstOrFail();

        $order->update(['status' => 'rejected']);

        return response()->json(['message' => 'Orçamento recusado.']);
    }
}
