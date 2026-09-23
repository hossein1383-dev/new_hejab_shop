<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncHeropostCitiesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncs_cities_from_api_response(): void
    {
        Http::fake([
            'heropost.ir/api/login' => Http::response(['status' => 200, 'data' => ['token' => 'test-token']]),
            'heropost.ir/api/post/cities' => Http::response([
                'status' => 200,
                'data' => [
                    ['id' => 1, 'name' => 'تهران', 'en_name' => 'Tehran', 'province_id' => 1],
                    ['id' => 2, 'name' => 'کرج', 'en_name' => 'Karaj', 'province_id' => 31],
                ],
            ]),
        ]);

        $this->artisan('heropost:sync-cities')->assertExitCode(0);

        $this->assertDatabaseHas('heropost_cities', ['heropost_city_id' => 1, 'name' => 'تهران', 'province_name' => 'تهران']);
        $this->assertDatabaseHas('heropost_cities', ['heropost_city_id' => 2, 'name' => 'کرج', 'province_name' => 'البرز']);
        $this->assertDatabaseCount('heropost_cities', 2);
    }

    public function test_fails_gracefully_when_login_fails(): void
    {
        Http::fake([
            'heropost.ir/api/login' => Http::response(['status' => 401, 'error' => 'invalid credentials']),
        ]);

        $this->artisan('heropost:sync-cities')->assertExitCode(1);
        $this->assertDatabaseCount('heropost_cities', 0);
    }

    public function test_verify_option_confirms_correct_province_mapping(): void
    {
        Http::fake([
            'heropost.ir/api/login' => Http::response(['status' => 200, 'data' => ['token' => 'test-token']]),
            'heropost.ir/api/post/cities/province/*' => function ($request) {
                // شهر مرکز درست را برای هر province_id برمی‌گرداند تا هر ۳۱ تا تایید شوند
                $capitals = \App\Console\Commands\SyncHeropostCitiesCommand::KNOWN_CAPITALS;
                preg_match('#/province/(\d+)#', $request->url(), $m);
                $provinceId = (int) ($m[1] ?? 0);

                return Http::response(['status' => 200, 'data' => [['id' => 1, 'name' => $capitals[$provinceId] ?? 'نامشخص']]]);
            },
        ]);

        $this->artisan('heropost:sync-cities', ['--verify' => true])
            ->expectsOutputToContain('نگاشت درست است')
            ->assertExitCode(0);
    }

    public function test_verify_option_flags_wrong_province_mapping(): void
    {
        Http::fake([
            'heropost.ir/api/login' => Http::response(['status' => 200, 'data' => ['token' => 'test-token']]),
            'heropost.ir/api/post/cities/province/*' => Http::response([
                'status' => 200,
                'data' => [['id' => 1, 'name' => 'یک شهر کاملاً نامرتبط']],
            ]),
        ]);

        $this->artisan('heropost:sync-cities', ['--verify' => true])
            ->expectsOutputToContain('نگاشت احتمالاً اشتباه است')
            ->assertExitCode(1);
    }
}
