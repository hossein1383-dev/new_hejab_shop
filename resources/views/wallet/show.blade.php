@extends('account.layout')

@section('title', 'کیف پول من')
@section('page-identifier', 'wallet')

@push('styles')
    @vite(['resources/css/components/header.css', 'resources/css/pages/account.css', 'resources/css/pages/wallet.css'])
@endpush

@section('account_content')
<h1 class="account-page__title">کیف پول من</h1>

<div class="wallet-balance-card">
    <div class="wallet-balance-card__info">
        <span class="wallet-balance-card__label">کیف پول</span>
        <span class="wallet-balance-card__amount">موجودی: {{ number_format($wallet->balance) }} تومان</span>
        <button type="button" class="wallet-balance-card__topup-btn" data-toggle-topup-form>
            <span>افزایش موجودی</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </button>
    </div>
    <svg class="wallet-balance-card__icon" width="90" height="90" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v2H5" stroke="currentColor" stroke-width="1.5" opacity="0.6"/>
        <rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="1.5" fill="rgba(255,255,255,0.15)"/>
        <path d="M14 13.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0z" fill="currentColor"/>
    </svg>
</div>

<div class="admin-form wallet-topup-form-wrap" data-topup-form-wrap hidden style="margin: var(--space-4) 0; border: 1px solid var(--border); border-radius: var(--radius-card); padding: var(--space-4);">
    <h2 style="font-size:1rem; margin: 0 0 var(--space-3);">شارژ کیف پول</h2>
    <div class="account-general-message" data-topup-message hidden></div>
    <form id="wallet-topup-form" style="display:flex; gap: var(--space-2); flex-wrap: wrap; align-items:flex-end;">
        @csrf
        <div class="account-field" style="flex:1; min-width:160px; margin-bottom:0;">
            <label for="topup-amount">مبلغ (تومان)</label>
            <input type="number" id="topup-amount" name="amount" min="10000" step="1000" placeholder="حداقل ۱۰,۰۰۰" required>
        </div>
        <button type="submit" class="account-btn-primary">شارژ از طریق درگاه</button>
    </form>
</div>

<h2 style="margin: var(--space-6) 0 var(--space-3); font-size: 1rem;">تاریخچه تراکنش‌ها</h2>

@forelse($transactions as $transaction)
    <div class="wallet-transaction">
        <div>
            <strong>{{ $transaction->type === 'credit' ? 'واریز' : 'برداشت' }}</strong>
            @if($transaction->note)
                <p class="wallet-transaction__note">{{ $transaction->note }}</p>
            @endif
            <span class="wallet-transaction__date">{{ $transaction->created_at->format('Y/m/d H:i') }}</span>
        </div>
        <span class="wallet-transaction__amount wallet-transaction__amount--{{ $transaction->type }}">
            {{ $transaction->type === 'credit' ? '+' : '−' }}{{ number_format($transaction->amount) }} تومان
        </span>
    </div>
@empty
    <div class="account-empty">
        <p>هنوز تراکنشی ثبت نشده است.</p>
    </div>
@endforelse
@endsection
