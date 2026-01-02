@php
    $size = $size ?? 18;
@endphp
@switch($name)
    @case('grid')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
        </svg>
    @break

    @case('receipt')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 3h12a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2V4a1 1 0 0 1 1-1Z"></path>
            <path d="M9 8h6"></path>
            <path d="M9 12h6"></path>
        </svg>
    @break

    @case('layers')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="m12 3 9 5-9 5-9-5Z"></path>
            <path d="m3 12 9 5 9-5"></path>
            <path d="m3 17 9 5 9-5"></path>
        </svg>
    @break

    @case('map')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 6 6-3 6 3 1 12-7 3-7-3 1-12Z"></path>
            <path d="M12 3v18"></path>
            <path d="m6 6 1 12"></path>
            <path d="m17 6-1 12"></path>
        </svg>
    @break

    @case('user')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 20a6 6 0 0 0-12 0"></path>
            <circle cx="12" cy="10" r="4"></circle>
        </svg>
    @break

    @case('users')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
    @break

    @case('building')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 21h18"></path>
            <path d="M6 21V5a1 1 0 0 1 1-1h6l5 5v12"></path>
            <path d="M13 4v5h5"></path>
            <path d="M9 9h1"></path>
            <path d="M9 13h1"></path>
            <path d="M9 17h1"></path>
        </svg>
    @break

    @case('database')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
            <path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"></path>
            <path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"></path>
        </svg>
    @break

    @case('search')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m21 21-4.35-4.35"></path>
        </svg>
    @break

    @case('bell')
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6.25 8A5.75 5.75 0 0 1 18 8c0 7 2 9 2 9H4s2-2 2.25-9Z"></path>
            <path d="M9.5 21a2.5 2.5 0 0 0 5 0"></path>
        </svg>
    @break

    @default
        <svg xmlns="http://www.w3.org/2000/svg" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
        </svg>
@endswitch
