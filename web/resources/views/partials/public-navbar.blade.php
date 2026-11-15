<header class="public-header" data-public-navbar>
    <div class="public-container public-navbar">
        
        <a href="{{ route('home') }}#home" class="public-brand assocmap-brand-link" aria-label="AssocMap home">
            <span class="public-brand-mark">
                <x-assocmap-mark />
            </span>

            <span class="public-brand-copy">
                <x-assocmap-wordmark />
                <small>Community. Connected.</small>
            </span>
        </a>

        <button type="button" class="public-menu-toggle" data-public-menu-toggle aria-controls="public-navigation" aria-expanded="false" hidden>
            <x-icon name="menu" /> <span>Menu</span>
        </button>
        <nav id="public-navigation" class="public-navigation" aria-label="Public navigation">
            <a href="{{ route('home') }}#home">Home</a>
            <a href="{{ route('home') }}#about">About</a>
            <a href="{{ route('home') }}#features">Features</a>
            <a href="{{ route('public.gis') }}" @if (request()->routeIs('public.gis')) aria-current="page" @endif>GIS Map</a>
            <a href="{{ route('home') }}#contact">Contact</a>
            <a href="{{ route('login') }}" class="public-nav-access">Login <x-icon name="arrow" /></a>
        </nav>
    </div>
</header>
