<script setup>
defineProps({
    compact: { type: Boolean, default: false },
});
</script>

<template>
    <span
        class="site-logo"
        :class="{ 'site-logo--compact': compact }"
        aria-hidden="true"
    >
        <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="logoGrad" x1="4" y1="4" x2="36" y2="36">
                    <stop offset="0%" stop-color="#6366f1" />
                    <stop offset="100%" stop-color="#22d3ee" />
                </linearGradient>
            </defs>
            <rect
                x="2"
                y="2"
                width="36"
                height="36"
                rx="10"
                fill="url(#logoGrad)"
            />
            <path
                d="M12 11h16a1.5 1.5 0 0 1 1.5 1.5v19A1.5 1.5 0 0 1 28 33H12a1.5 1.5 0 0 1-1.5-1.5v-19A1.5 1.5 0 0 1 12 11z"
                fill="rgba(255,255,255,0.18)"
                stroke="rgba(255,255,255,0.55)"
                stroke-width="1.2"
            />
            <path
                d="M15 17h10M15 21h10M15 25h6"
                stroke="rgba(255,255,255,0.85)"
                stroke-width="1.6"
                stroke-linecap="round"
            />
            <circle cx="29" cy="29" r="7" fill="#0c0e16" />
            <path
                d="M26.5 29l1.8 1.8 3.5-3.6"
                stroke="#34d399"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
        </svg>
    </span>
</template>
