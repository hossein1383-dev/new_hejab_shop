<div class="admin-orders-mobile">
    @forelse($reviews as $review)
        <div class="admin-order-card">
            <div class="admin-order-card__row">
                <strong>{{ $review->product->name }}</strong>
                <span class="admin-status-badge admin-status-badge--{{ $review->status === 'approved' ? 'paid' : ($review->status === 'rejected' ? 'cancelled' : 'pending_payment') }}">{{ $review->status }}</span>
            </div>
            <div class="admin-order-card__row admin-order-card__row--muted">
                <span>{{ $review->user->name }}</span>
                <span>{{ str_repeat('★', $review->rating) }}</span>
            </div>
            @if($review->comment)
                <p style="font-size:0.85rem; color:var(--text-muted); margin: var(--space-2) 0;">{{ $review->comment }}</p>
            @endif
            @if($review->status === 'pending')
                <div class="admin-order-card__row">
                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="approved">
                        <button type="submit" class="admin-table__link">تایید</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="rejected">
                        <button type="submit" class="admin-table__link admin-table__link--danger">رد</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <p class="admin-empty">نظری یافت نشد.</p>
    @endforelse
</div>
