@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'error-ui']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
