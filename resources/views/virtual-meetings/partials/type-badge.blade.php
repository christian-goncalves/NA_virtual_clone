@php
    $badgeClass = $badgeClass ?? 'vm-badge-type-theme';
    $badgeLabel = isset($badgeLabel) ? trim((string) $badgeLabel) : '';
    $badgeDescriptionExplicit = (bool) ($badgeDescriptionExplicit ?? false);
    $badgeDescriptionValue = $badgeDescriptionExplicit ? trim((string) ($badgeDescription ?? '')) : '';

    $normalizedBadge = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($badgeLabel));
    $badgeIconClass = 'fa-solid fa-tag';

    if (str_contains($normalizedBadge, 'aberta') || str_contains($normalizedBadge, 'aberto')) {
        $badgeIconClass = 'fa-solid fa-user-group';
    } elseif (str_contains($normalizedBadge, 'fechada') || str_contains($normalizedBadge, 'fechado')) {
        $badgeIconClass = 'fa-solid fa-lock';
    } elseif (str_contains($normalizedBadge, 'estudo')) {
        $badgeIconClass = 'fa-solid fa-book-open';
    }
@endphp

<span class="vm-badge {{ $badgeClass }}">
    <i class="{{ $badgeIconClass }} text-[0.62rem]" aria-hidden="true"></i>
    <span>
        {{ $badgeLabel }}@if($badgeDescriptionValue !== '') - {{ $badgeDescriptionValue }}@endif
    </span>
</span>

