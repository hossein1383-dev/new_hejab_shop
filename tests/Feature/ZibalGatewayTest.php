<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\PaymentGateways\ZibalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZibalGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_initiate_sends_amount_in_rial_and_returns_redirect_url(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 123456], 200),
        ]);

        $order = new Order(['order_number' => 'ORD-TEST-1', 'total' => 150000, 'shipping_phone' => '09121234567']);
        $gateway = new ZibalGateway('zibal-test-merchant');

        $result = $gateway->initiate($order);

        $this->assertEquals('https://gateway.zibal.ir/start/123456', $result['redirect_url']);
        $this->assertEquals('123456', $result['reference']);

        Http::assertSent(function ($request) {
            // ۱۵۰,۰۰۰ تومان باید ۱,۵۰۰,۰۰۰ ریال ارسال شود
            return $request['amount'] === 1500000 && $request['merchant'] === 'zibal-test-merchant';
        });
    }

    public function test_initiate_throws_exception_when_zibal_rejects_request(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 102, 'message' => 'merchant not found'], 200),
        ]);

        $order = new Order(['order_number' => 'ORD-TEST-2', 'total' => 150000, 'shipping_phone' => '09121234567']);
        $gateway = new ZibalGateway('invalid-merchant');

        $this->expectException(\RuntimeException::class);
        $gateway->initiate($order);
    }

    public function test_verify_converts_rial_back_to_toman_on_success(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 100, 'amount' => 1500000, 'refNumber' => 'REF1'], 200),
        ]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $result = $gateway->verify(['trackId' => '123456']);

        $this->assertTrue($result['success']);
        $this->assertEquals('123456', $result['reference']);
        $this->assertEquals(150000, $result['amount']); // برگشت به تومان
    }

    public function test_verify_treats_already_verified_as_success(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 201, 'amount' => 1500000], 200),
        ]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $result = $gateway->verify(['trackId' => '123456']);

        $this->assertTrue($result['success']);
    }

    public function test_verify_fails_gracefully_on_unsuccessful_result_code(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/verify' => Http::response(['result' => 202, 'amount' => 1500000], 200),
        ]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $result = $gateway->verify(['trackId' => '123456']);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, $result['amount']);
    }

    public function test_initiate_generic_sends_correct_payload_for_non_order_payments(): void
    {
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response(['result' => 100, 'trackId' => 999], 200),
        ]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $result = $gateway->initiateGeneric(50000, 'WALLET-1-123', 'شارژ کیف پول', 'https://example.com/callback', '09121234567');

        $this->assertEquals('999', $result['reference']);
        Http::assertSent(function ($request) {
            return $request['amount'] === 500000
                && $request['orderId'] === 'WALLET-1-123'
                && $request['callbackUrl'] === 'https://example.com/callback';
        });
    }

    public function test_verify_returns_failure_immediately_when_track_id_missing(): void
    {
        Http::fake(); // نباید هیچ درخواستی ارسال شود

        $gateway = new ZibalGateway('zibal-test-merchant');
        $result = $gateway->verify(['success' => '0']); // کاربر بدون trackId برگشته

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_refund_returns_true_when_zibal_confirms(): void
    {
        Http::fake(['api.zibal.ir/*' => Http::response(['result' => 100])]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $this->assertTrue($gateway->refund('12345', 100000));
    }

    public function test_refund_returns_false_without_crashing_when_service_not_available(): void
    {
        // دقیقاً همان چیزی که وقتی «بانکداری شرکتی» فعال نیست رخ می‌دهد
        Http::fake(['api.zibal.ir/*' => Http::response(['result' => 999, 'message' => 'سرویس فعال نیست'], 400)]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $this->assertFalse($gateway->refund('12345', 100000));
    }

    public function test_refund_returns_false_on_network_exception(): void
    {
        Http::fake(['api.zibal.ir/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

        $gateway = new ZibalGateway('zibal-test-merchant');
        $this->assertFalse($gateway->refund('12345', 100000));
    }
}
