<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreate($request->user());

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => [
                'balance' => $wallet->balance,
                'transactions' => $wallet->transactions()->take(20)->get(),
            ],
        ]);
    }
}
