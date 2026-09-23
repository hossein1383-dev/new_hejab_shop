@extends('admin.layout')
@section('title', 'دسته‌های وبلاگ')
@section('content')
<a href="{{ route('admin.blog.index') }}" class="admin-back-link">← بازگشت به مطالب</a>
<h1 class="admin-page-title">دسته‌های وبلاگ</h1>

<form method="POST" action="{{ route('admin.blog.categories.store') }}" class="admin-filter-bar">
    @csrf
    <input type="text" name="name" placeholder="نام دسته جدید..." required>
    <button type="submit">افزودن</button>
</form>
@error('name')<p style="color:var(--danger); font-size:0.85rem;">{{ $message }}</p>@enderror

<div class="admin-orders-desktop">
    <table class="admin-table">
        <thead><tr><th>نام</th><th>تعداد مطلب</th><th></th></tr></thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->posts_count }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.blog.categories.destroy', $category) }}" style="display:inline" data-confirm="حذف؟">
                            @csrf @method('DELETE')
                            <button type="submit" class="admin-table__link admin-table__link--danger">حذف</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="admin-empty">دسته‌ای یافت نشد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
