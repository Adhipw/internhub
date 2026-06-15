<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="notranslate">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        @if(isset($seo))
            <title>{{ $seo['title'] }} - InterHub</title>
            <meta name="description" content="{{ $seo['description'] }}">
            
            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="{{ $seo['type'] }}">
            <meta property="og:url" content="{{ $seo['url'] }}">
            <meta property="og:title" content="{{ $seo['title'] }} - InterHub">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:image" content="{{ $seo['image'] }}">

            <!-- Twitter -->
            <meta property="twitter:card" content="summary_large_image">
            <meta property="twitter:url" content="{{ $seo['url'] }}">
            <meta property="twitter:title" content="{{ $seo['title'] }} - InterHub">
            <meta property="twitter:description" content="{{ $seo['description'] }}">
            <meta property="twitter:image" content="{{ $seo['image'] }}">
        @else
            <title>InterHub - Platform Karir Magang Terbesar Indonesia</title>
            <meta name="description" content="InterHub adalah platform karir pencarian lowongan magang terbaik untuk mahasiswa Indonesia. Temukan lowongan impianmu dengan pencocokan AI real-time.">
            
            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website">
            <meta property="og:url" content="{{ url()->current() }}">
            <meta property="og:title" content="InterHub - Platform Karir Magang Terbesar Indonesia">
            <meta property="og:description" content="InterHub adalah platform karir pencarian lowongan magang terbaik untuk mahasiswa Indonesia. Temukan lowongan impianmu dengan pencocokan AI real-time.">
            <meta property="og:image" content="{{ asset('brand/logo-mark.svg') }}">

            <!-- Twitter -->
            <meta property="twitter:card" content="summary_large_image">
            <meta property="twitter:url" content="{{ url()->current() }}">
            <meta property="twitter:title" content="InterHub - Platform Karir Magang Terbesar Indonesia">
            <meta property="twitter:description" content="InterHub adalah platform karir pencarian lowongan magang terbaik untuk mahasiswa Indonesia. Temukan lowongan impianmu dengan pencocokan AI real-time.">
            <meta property="twitter:image" content="{{ asset('brand/logo-mark.svg') }}">
        @endif

        <link rel="icon" type="image/svg+xml" href="/brand/logo-mark.svg">
        
        <!-- PWA Mobile Web App Settings -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#2563eb">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <link rel="apple-touch-icon" href="/brand/logo-mark.svg">
        
        <script>
            window.__APP_CONFIG__ = @json([
                'recaptchaSiteKey' => config('services.recaptcha.site_key'),
            ]);

            window.__internhubRenderAllRecaptcha = function () {
                if (!window.grecaptcha || !window.grecaptcha.render) {
                    return;
                }

                document.querySelectorAll('.internhub-recaptcha').forEach(function (element) {
                    if (element.dataset.rendered === 'true') {
                        return;
                    }

                    var siteKey = element.dataset.sitekey || window.__APP_CONFIG__?.recaptchaSiteKey;

                    if (!siteKey) {
                        return;
                    }

                    try {
                        window.grecaptcha.render(element, {
                            sitekey: siteKey,
                            callback: function (token) {
                                element.dispatchEvent(new CustomEvent('recaptcha-token', {
                                    bubbles: true,
                                    detail: token,
                                }));
                            },
                            'expired-callback': function () {
                                element.dispatchEvent(new CustomEvent('recaptcha-token', {
                                    bubbles: true,
                                    detail: '',
                                }));
                            },
                        });
                        element.dataset.rendered = 'true';
                    } catch (error) {
                        console.error('Failed to render reCAPTCHA fallback:', error);
                    }
                });
            };

            window.addEventListener('load', function () {
                window.__internhubRenderAllRecaptcha();
                setTimeout(window.__internhubRenderAllRecaptcha, 1000);
                setTimeout(window.__internhubRenderAllRecaptcha, 3000);
            });
        </script>
        <script src="https://www.google.com/recaptcha/api.js?onload=__internhubRenderAllRecaptcha&render=explicit" async defer></script>
        @vite(['resources/js/app.ts', 'resources/css/app.css'], 'build')

    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
