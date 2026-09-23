<?php

namespace App\Http\Controllers;

use App\Services\WalletTopupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletTopupController extends Controller
{
    public function __construct(private readonly WalletTopupService $walletTopupService)
    {
    }

    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = $this->walletTopupService->initiate($request->user(), $data['amount']);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'در حال انتقال به درگاه پرداخت...',
            'data' => ['redirect_url' => $result['redirect_url']],
        ], 201);
    }

    public function callback(Request $request): RedirectResponse
    {
        $topup = $this->walletTopupService->handleCallback($request->all());

        return redirect()->route('wallet.show')
            ->with($topup->status === 'paid' ? 'order_success' : 'order_error', $topup->status === 'paid'
                ? 'کیف پول شما با موفقیت شارژ شد.'
                : 'شارژ کیف پول ناموفق بود. مبلغ کسر نشد.');
    }
}
