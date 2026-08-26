@php
    // $exception is absent when this view is rendered outside a real exception.
    $reason = isset($exception) ? trim((string) $exception->getMessage()) : '';
@endphp

<x-error-page
    code="403"
    title="Access denied"
    :message="$reason !== '' ? $reason : 'You do not have permission to view this page. If you believe that is a mistake, get in touch and we will sort it out.'"
/>
