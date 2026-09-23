<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\HeropostCity;
use App\Services\HeropostClient;
use App\Services\ShippingGateways\FlatRateShippingGateway;
use App\Services\ShippingGateways\HeropostShippingGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HeropostShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_real_price_when_address_has_heropost_city_id(): void
    {
        $client = $this->createMock(HeropostClient::class);
        $client->method('calculatePrice')->willReturn(['TotalPrice' => 850000]); // ریال

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $address = new Address(['heropost_city_id' => 42]);

        $cost = $gateway->calculateCost($address, 600, 150000);

        $this->assertEquals(85000, $cost); // برگشت به تومان
    }

    public function test_sends_non_standard_package_field_even_though_docs_call_it_optional(): void
    {
        // باگ واقعی: مستندات هیروپست NonStandardPackage را «اختیاری» می‌دانست
        // ولی API واقعی بدون آن خطای ۴۲۲ می‌دهد.
        $client = $this->createMock(HeropostClient::class);
        $client->expects($this->once())
            ->method('calculatePrice')
            ->with($this->callback(fn ($payload) => array_key_exists('NonStandardPackage', $payload)))
            ->willReturn(['TotalPrice' => 85000]);

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $gateway->calculateCost(new Address(['heropost_city_id' => 42]), 600, 150000);
    }

    public function test_falls_back_when_heropost_returns_array_error_instead_of_string(): void
    {
        Http::fake([
            'heropost.ir/api/login' => Http::response(['status' => 200, 'data' => ['token' => 'test-token']]),
            'heropost.ir/api/post/parcel/price' => Http::response(['status' => 422, 'error' => ['غیر استاندارد الزامی می باشد.']], 422),
        ]);

        $client = new \App\Services\HeropostClient('https://heropost.ir/api', 'u', 'p', 's');
        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());

        // نباید کرش کند — باید امن به نرخ ثابت برگردد
        $cost = $gateway->calculateCost(new Address(['heropost_city_id' => 42]), 600, 150000);

        $this->assertEquals(50000, $cost);
    }

    public function test_falls_back_to_flat_rate_when_address_has_no_heropost_city(): void
    {
        $client = $this->createMock(HeropostClient::class);
        $client->expects($this->never())->method('calculatePrice'); // نباید اصلاً صدا زده شود

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $address = new Address(['heropost_city_id' => null]);

        $cost = $gateway->calculateCost($address, 600, 150000);

        $this->assertEquals(50000, $cost); // نرخ ثابت Fallback
    }

    public function test_falls_back_to_flat_rate_when_api_call_fails(): void
    {
        $client = $this->createMock(HeropostClient::class);
        $client->method('calculatePrice')->willThrowException(new \RuntimeException('قطعی سرویس'));

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $address = new Address(['heropost_city_id' => 42]);

        $cost = $gateway->calculateCost($address, 600, 150000);

        $this->assertEquals(50000, $cost); // به‌جای کرش کل Checkout، نرخ ثابت
    }

    public function test_sends_parcel_value_in_rial_and_correct_packet_type(): void
    {
        // باگ واقعی: ParcelValue باید ریال باشد (نه تومان مثل بقیه پروژه)،
        // و PacketTypeID=1 برای این حساب هیروپست مجاز نبود.
        $client = $this->createMock(HeropostClient::class);
        $client->expects($this->once())
            ->method('calculatePrice')
            ->with($this->callback(fn ($payload) => $payload['ParcelValue'] === 1500000 && $payload['PacketTypeID'] === 2))
            ->willReturn(['TotalPrice' => 85000]);

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $gateway->calculateCost(new Address(['heropost_city_id' => 42]), 600, 150000); // ۱۵۰,۰۰۰ تومان
    }

    public function test_heropost_cities_endpoint_filters_by_province(): void
    {
        HeropostCity::create(['heropost_city_id' => 1, 'name' => 'تهران', 'province_name' => 'تهران']);
        HeropostCity::create(['heropost_city_id' => 2, 'name' => 'کرج', 'province_name' => 'البرز']);

        $response = $this->getJson('/heropost-cities?province=' . urlencode('تهران'));

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('تهران'));
        $this->assertFalse($names->contains('کرج'));
    }

    public function test_sends_selected_service_type_id_not_hardcoded_default(): void
    {
        // بخش ۵۲: مشتری می‌تواند «ویژه» (۳) را به‌جای «پیشتاز» (۱) پیش‌فرض انتخاب کند
        $client = $this->createMock(HeropostClient::class);
        $client->expects($this->once())
            ->method('calculatePrice')
            ->with($this->callback(fn ($payload) => $payload['ServiceTypeID'] === 3))
            ->willReturn(['TotalPrice' => 85000]);

        $gateway = new HeropostShippingGateway($client, new FlatRateShippingGateway());
        $gateway->calculateCost(new Address(['heropost_city_id' => 42]), 600, 150000, 3);
    }
}
