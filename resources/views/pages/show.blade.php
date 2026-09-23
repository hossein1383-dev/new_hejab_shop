@extends('layouts.app')

@section('title', $page->meta_title ?? $page->title)

@section('content')
<div style="max-width: 720px; margin: 40px auto; padding: 0 16px; font-family: inherit;">
    <h1>{{ $page->title }}</h1>
    <div style="line-height: 2;">{!! nl2br(e($page->content)) !!}</div>
</div>
@endsection
