@extends('layouts.app')

@section('content')
    @include('components.header.header-mobile')
    @include('components.header.header-desktop')

    <main>
        @yield('body')
    </main>

    @include('components.footer')
@endsection
