<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\HeropostClient;
use Illuminate\Console\Command;

/**
 * بخش ۵۲: نمایش پاسخ خام API ثبت مرسوله هیروپست برای یک سفارش مشخص.
 * ⚠️ با مستندات کامل اصلاح شد: فیلدهای Customer* اطلاعات گیرنده (مشتری
 * سفارش) هستند، نه فرستنده؛ و اصلاً فیلد «فرستنده»/FromCityID در این
 * API وجود ندارد (آدرس فروشگاه در پنل خودِ هیروپست تنظیم می‌شود).
 */
class DebugCreateHeropostParcelCommand extends Command
{
    protected $signature = 'heropost:debug-create-parcel {order_number}';
    protected $description = 'نمایش کامل پاسخ خام API ثبت مرسوله برای یک سفارش (بدون ذخیره چیزی)';

    public function handle(HeropostClient $client): int
    {
        $order = Order::where('order_number', $this->argument('order_number'))->with('items.product')->first();

        if (! $order) {
            $this->error('سفارشی با این شماره پیدا نشد.');

            return self::FAILURE;
        }

        if (! $order->shipping_heropost_city_id) {
            $this->error('این سفارش شناسه شهر هیروپست ندارد.');

            return self::FAILURE;
        }

        $order->load('items.product', 'user');
        $totalWeightGrams = $order->items->sum(function ($item) {
            $weightKg = $item->product?->weight ?? null;

            return ($weightKg ? (int) round($weightKg * 1000) : 300) * $item->quantity;
        });

        $payload = [
            'ClientOrderID' => (string) $order->id,
            'CustomerNID' => '',
            'CustomerName' => $order->shipping_name,
            'CustomerFamily' => '',
            'CustomerMobile' => $order->shipping_phone,
            'CustomerEmail' => $order->user?->email ?? '',
            'CustomerPostalCode' => $order->shipping_postal_code,
            'CustomerAddress' => $order->shipping_address_line,
            'ParcelContent' => 'پوشاک',
            'IsReadyToAccept' => 0,
            'PrintRequest' => 0,
            'PrePrintBarcode' => '',
            'ToCityID' => $order->shipping_heropost_city_id,
            'ParcelCategoryID' => 3,
            'BoxSizeID' => 8,
            'ServiceTypeID' => $order->shipping_service_type_id ?: 1,
            'PacketTypeID' => 2,
            'Weight' => max($totalWeightGrams, 200),
            'ParcelValue' => $order->total * 10,
            'PrePaidAmount' => 0,
            'CollectNeed' => 0,
            'NonStandardPackage' => 0,
            'SMSService' => 0,
            'TwoReceiptant' => 0,
        ];

        $this->line("Payload ارسالی:");
        $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        try {
            $result = $client->createParcel($payload);
            $this->info("\n✓ پاسخ موفق خام هیروپست:");
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            $this->error("\n✗ خطا: " . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
