<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = $request->user()->clients()->orderBy('name')->get();

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $client = $request->user()->clients()->create($data);

        return response()->json($client, 201);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClient($request, $client);
        $client->delete();

        return response()->json(null, 204);
    }

    private function authorizeClient(Request $request, Client $client): void
    {
        if ($client->user_id !== $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
