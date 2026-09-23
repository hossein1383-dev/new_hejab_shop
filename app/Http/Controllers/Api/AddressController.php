<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Models\Address;
use App\Services\AddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addressService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => null,
            'data' => $request->user()->addresses,
        ]);
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addressService->create($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'آدرس با موفقیت ذخیره شد.',
            'data' => $address,
        ], 201);
    }

    public function update(StoreAddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $address = $this->addressService->update($address, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'آدرس ویرایش شد.',
            'data' => $address,
        ]);
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->addressService->delete($address);

        return response()->json([
            'success' => true,
            'message' => 'آدرس حذف شد.',
            'data' => [],
        ]);
    }
}
