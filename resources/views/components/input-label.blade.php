@props(['value'])

<label {{ $attributes->merge(['class' => 'label-ui']) }}>
    {{ $value ?? $slot }}
</label>
