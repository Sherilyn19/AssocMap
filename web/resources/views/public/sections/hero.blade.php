@php
    $heroImage = collect(['webp', 'jpg', 'avif'])
        ->map(fn ($extension) => 'images/public/landing-hero.'.$extension)
        ->first(fn ($path) => is_file(public_path($path)));
@endphp
<section id="home" class="public-hero" aria-labelledby="public-hero-title">
    @if ($heroImage)
        <img class="public-hero-photo" src="{{ asset($heroImage) }}"
            @if ($heroImage === 'images/public/landing-hero.webp')
                srcset="{{ asset('images/public/landing-hero-mobile.webp') }} 960w, {{ asset($heroImage) }} 2400w"
                sizes="100vw"
            @endif
            alt="" width="2400" height="1600" fetchpriority="high">
    @endif
    <div class="public-hero-overlay" aria-hidden="true"></div>
    <div class="public-container public-hero-content">
        <div class="public-hero-copy">
            <p class="public-eyebrow public-hero-enter">Welcome to AssocMap</p>
            <h1 id="public-hero-title" class="public-hero-enter">Mapping communities.<br>Monitoring <em>progress.</em></h1>
            <p class="public-hero-description public-hero-enter">Connecting livelihood associations, projects, and geographic information for a clearer view of program progress.</p>
            <div class="public-actions public-hero-enter">
                <a href="{{ route('public.gis') }}" class="public-button public-button-light">Explore GIS Map <x-icon name="arrow" /></a>
                <a href="#about" class="public-button public-button-ghost">Discover AssocMap <x-icon name="chevron-down" /></a>
            </div>
        </div>
        <div class="public-hero-bottom">
            <a href="#about" class="public-scroll-link"><span class="public-scroll-icon"><x-icon name="chevron-down" /></span> Explore the bigger picture</a>
            <div class="public-hero-meta">
                <span class="public-hero-region">SAAD PHASE 2 REGION 7</span>
            </div>
        </div>
    </div>
</section>

<section class="public-intro-band" aria-labelledby="public-philosophy-title">
    <div class="public-container public-intro-content">
        <div class="public-intro-message">
            <p class="public-intro-eyebrow">Platform Philosophy</p>

            <h4 id="public-philosophy-title" class="public-intro-title">
                People at the center.
                <span>Information that connects.</span>
            </h4>
        </div>

        <p class="public-intro-summary">
            From association records to geographic context, bring the pieces of community livelihood monitoring together.
        </p>
    </div>
</section>

