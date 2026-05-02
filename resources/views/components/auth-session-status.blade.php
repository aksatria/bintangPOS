@if ($status)
    <div {{ $attributes->merge(['class' => 'status-ui']) }}>
        {{ $status }}
    </div>
@endif
