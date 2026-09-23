<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * صفحه شبیه‌سازی درگاه پرداخت — فقط برای Development/Test.
     * ⚠️ این صفحه هرگز نباید در Production در دسترس باشد (باید قبل از
     * Deploy واقعی با یک Gateway حقیقی جایگزین شود — بخش ۱۴).
     */
    public function showFakeGateway(string $reference): View
    {
        $payment = Payment::where('reference', $reference)->first();

        if ($payment) {
            return view('payment.fake-gateway', [
                'amount' => $payment->amount,
                'reference' => $reference,
                'callbackUrl' => route('payment.callback'),
            ]);
        }

        // اگر Payment سفارش نبود، شاید شارژ کیف پول باشد (بخش ۱۸)
        $topup = \App\Models\WalletTopup::where('reference', $reference)->firstOrFail();

        return view('payment.fake-gateway', [
            'amount' => $topup->amount,
            'reference' => $reference,
            'callbackUrl' => route('wallet.topup.callback'),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $payment = $this->paymentService->handleCallback($request->all());

        return redirect()->route('orders.confirmation', ['order' => $payment->order->order_number]);
    }
}
