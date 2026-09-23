<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\CartService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OtpAuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly CartService $cartService,
    ) {
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $this->otpService->requestCode($request->validated('phone'));

        return response()->json([
            'success' => true,
            'message' => 'کد تایید برای شماره شما ارسال شد.',
            'data' => [],
        ]);
    }

    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $guestSessionId = $request->session()->getId();

        try {
            $user = $this->otpService->verifyCode($request->validated('phone'), $request->validated('code'));
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => []], 422);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $this->cartService->mergeGuestCartIntoUser($user, $guestSessionId);

        return response()->json([
            'success' => true,
            'message' => 'ورود با موفقیت انجام شد.',
            'data' => ['user' => new UserResource($user)],
        ]);
    }
}
