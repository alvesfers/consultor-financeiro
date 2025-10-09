@if (session('status'))
    <div class="tw-alert tw-alert-success tw-mb-4">
        <span>{{ session('status') }}</span>
    </div>
@endif
