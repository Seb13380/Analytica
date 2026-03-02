@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 focus:outline-none transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 focus:outline-none focus:border-transparent transition duration-150 ease-in-out';

$style = ($active ?? false)
    ? 'border-color:#C9A84C;color:#1C1916;font-weight:500;'
    : 'color:#5C5449;border-color:transparent;';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} style="{{ $style }}" onmouseover="if(!this.classList.contains('border-b-2') || this.style.borderColor!='rgb(201, 168, 76)') { this.style.color='#1C1916'; }" onmouseout="if(this.style.borderColor!='rgb(201, 168, 76)') { this.style.color='#5C5449'; }">
    {{ $slot }}
</a>
