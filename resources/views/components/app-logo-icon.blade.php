{{-- The Yeah Kenyan mark. A raster logo, so no fill-current styling applies. --}}
<img
    src="{{ asset('images/logo.png') }}"
    alt="{{ config('site.name', config('app.name')) }}"
    {{ $attributes->merge(['class' => 'object-contain']) }}
>
