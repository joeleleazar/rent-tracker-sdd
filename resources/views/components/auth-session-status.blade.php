@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2 fw-semibold text-success']) }} role="status">
        <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
        {{ $status }}
    </div>
@endif
