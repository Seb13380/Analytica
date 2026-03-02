@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium focus:outline-none transition duration-150 ease-in-out';

$style = ($active ?? false)
    ? 'border-color:#C9A84C;color:#1C1916;background:rgba(201,168,76,0.06);'
    : 'color:#5C5449;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $style }}">
    {{ $slot }}
</a>
