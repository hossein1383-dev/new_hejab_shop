<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Models\User;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\DB;

/**
 * شارژ کیف پول از طریق درگاه پرداخت — بخش ۱۸. دقیقاً همان الگوی
 * PaymentService (رزرو رکورد pending، Verify واقعی، Idempotent بودن
 * Callback تکراری) ولی بدون وابستگی به Order.
 */
class WalletTopupService
{
    private const MIN_AMOUNT = 10000; // تومان — جلوگیری از شارژ مبالغ ناچیز که هزینه Gateway را توجیه نمی‌کند

    public function __construct(
        private readonly PaymentGatewayContract $gateway,
        private readonly WalletService $walletService,
    ) {
    }

    /** @return array{redirect_url: string, reference: string} */
    public function initiate(User $user, int $amount): array
    {
        if ($amount < self::MIN_AMOUNT) {
            throw new \DomainException('حداقل مبلغ شارژ کیف پول ' . number_format(self::MIN_AMOUNT) . ' تومان است.');
        }

        $result = $this->gateway->initiateGeneric(
            $amount,
            'WALLET-' . $user->id . '-' . now()->timestamp,
            'شارژ کیف پول',
            route('wallet.topup.callback'),
            $user->phone
        );

        WalletTopup::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'reference' => $result['reference'],
            'gateway' => config('services.zibal.driver') === 'zibal' ? 'zibal' : 'fake',
            'status' => 'pending',
        ]);

        return $result;
    }

    /**
     * بخش ۳۵ (Idempotency): اگر همین Callback دوبار برسد (مثلاً کاربر Refresh
     * کند)، فقط بار اول موجودی شارژ می‌شود.
     */
    public function handleCallback(array $callbackData): WalletTopup
    {
        $verification = $this->gateway->verify($callbackData);

        return DB::transaction(function () use ($verification) {
            $topup = WalletTopup::where('reference', $verification['reference'])
                ->lockForUpdate()
                ->first();

            if (! $topup) {
                throw new \DomainException('تراکنش شارژ کیف پول یافت نشد.');
            }

            if ($topup->status !== 'pending') {
                return $topup; // قبلاً پردازش شده — همان نتیجه قبلی
            }

            if (! $verification['success'] || $verification['amount'] !== $topup->amount) {
                $topup->update(['status' => 'failed']);

                return $topup;
            }

            $topup->update(['status' => 'paid', 'paid_at' => now()]);
            $this->walletService->credit($topup->user, $topup->amount, $topup->reference, 'شارژ از طریق درگاه پرداخت');

            return $topup;
        });
    }
}
