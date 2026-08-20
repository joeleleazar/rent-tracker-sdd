@props(['value'])

<label {{ $attributes->merge(['class' => 'etiqueta-senior']) }}>
    {{ $value ?? $slot }}
</label>
