<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead>
            <tr>
                <th>محصول</th>
                <th>کاربر</th>
                <th>امتیاز</th>
                <th>نظر</th>
                <th>وضعیت</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
                <tr>
                    <td>{{ $review->product->name }}</td>
                    <td>{{ $review->user->name }}</td>
                    <td>{{ str_repeat('★', $review->rating) }}</td>
                    <td style="max-width: 300px;">{{ $review->comment }}</td>
                    <td><span class="admin-status-badge admin-status-badge--{{ $review->status === 'approved' ? 'paid' : ($review->status === 'rejected' ? 'cancelled' : 'pending_payment') }}">{{ $review->status }}</span></td>
                    <td>
                        @if($review->status === 'pending')
                            <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}" style="display:inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="admin-table__link">تایید</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reviews.moderate', $review) }}" style="display:inline">
                                @csrf @method('PUT')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="admin-table__link admin-table__link--danger">رد</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="admin-empty">نظری یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
