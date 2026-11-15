@extends('layouts.public')
@section('title', 'GIS Unavailable · AssocMap')
@section('content')
    <section class="gis-public-main"><h1>GIS unavailable</h1><p class="notice" role="alert">{{ $message }}</p>@if(request()->routeIs('public.gis'))<a class="button button-primary" href="{{ route('public.gis') }}">Try the map again</a>@else<a class="button" href="{{ route('login') }}">Sign in</a>@endif <a class="button" href="{{ route('home') }}">Back to AssocMap</a></section>
@endsection
