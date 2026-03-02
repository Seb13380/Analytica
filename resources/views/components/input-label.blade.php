@props(['value'])

<label {{ $attributes->merge(['class' => 'label-luxury']) }}>
    {{ $value ?? $slot }}
</label>
