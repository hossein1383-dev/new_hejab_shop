@extends('admin.layout')

@section('title', 'مدیریت نظرات')

@section('content')
<h1 class="admin-page-title">نظرات</h1>

<form method="get" class="admin-filter-bar">
    <select name="status" data-auto-submit>
        <option value="pending" @selected(request('status', 'pending') === 'pending')>در انتظار تایید</option>
        <option value="approved" @selected(request('status') === 'approved')>تاییدشده</option>
        <option value="rejected" @selected(request('status') === 'rejected')>ردشده</option>
    </select>
</form>

@include('components.admin.reviews-table-mobile')
@include('components.admin.reviews-table-desktop')

<div class="admin-pagination">
    {{ $reviews->onEachSide(1)->links() }}
</div>
@endsection
