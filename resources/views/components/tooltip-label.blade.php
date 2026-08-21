@props([
    'text',
])

<span
    tabindex="0"
    {{ $attributes->merge(['class' => 'group relative inline-flex cursor-help border-b border-dotted border-current outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2']) }}
>
    {{ $slot }}
    <span
        role="tooltip"
        class="pointer-events-none absolute bottom-full left-0 z-10 mb-2 w-56 rounded-md bg-slate-900 px-3 py-2 text-xs font-normal leading-relaxed text-white opacity-0 shadow-sm transition-opacity group-hover:opacity-100 group-focus-within:opacity-100"
    >
        {{ $text }}
    </span>
</span>
