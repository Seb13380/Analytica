@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm']) }} style="color:#9B7A2A;">
        {{ $status }}
    </div>
@endif
