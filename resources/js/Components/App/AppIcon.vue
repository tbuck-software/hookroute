<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 18 });
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'dashboard'">
            <rect x="3" y="3" width="7" height="7" />
            <rect x="14" y="3" width="7" height="7" />
            <rect x="3" y="14" width="7" height="7" />
            <rect x="14" y="14" width="7" height="7" />
        </template>
        <template v-else-if="name === 'source'">
            <path d="M4 12h11" />
            <path d="m11 8 4 4-4 4" />
            <path d="M19 5v14" />
        </template>
        <template v-else-if="name === 'destination'">
            <path d="M5 5v14" />
            <path d="M9 12h11" />
            <path d="m16 8 4 4-4 4" />
        </template>
        <template v-else-if="name === 'route'">
            <circle cx="5" cy="6" r="2" />
            <circle cx="19" cy="18" r="2" />
            <path d="M7 6h3a4 4 0 0 1 4 4v4a4 4 0 0 0 4 4" />
        </template>
        <template v-else-if="name === 'event'">
            <path d="M13 2 4.5 13h7L11 22l8.5-12h-7L13 2Z" />
        </template>
        <template v-else-if="name === 'team'">
            <circle cx="9" cy="8" r="3" />
            <circle cx="17" cy="9" r="2" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M15 15a4.5 4.5 0 0 1 6 4" />
        </template>
        <template v-else-if="name === 'user'">
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21a8 8 0 0 1 16 0" />
        </template>
        <template v-else-if="name === 'logout'">
            <path d="M10 4H5v16h5" />
            <path d="M14 8l4 4-4 4" />
            <path d="M18 12H9" />
        </template>
        <template v-else-if="name === 'webhook'">
            <circle cx="6" cy="12" r="3" />
            <circle cx="18" cy="6" r="3" />
            <circle cx="18" cy="18" r="3" />
            <path d="m8.5 10.5 7-3M8.5 13.5l7 3" />
        </template>
        <template v-else-if="name === 'discord'">
            <path d="M7 7.5a13 13 0 0 1 10 0l1.5 9a10 10 0 0 1-3 1.5l-1-1.5" />
            <path d="M17 7.5a13 13 0 0 0-10 0l-1.5 9a10 10 0 0 0 3 1.5l1-1.5" />
            <circle
                cx="9.5"
                cy="12.5"
                r="1"
                fill="currentColor"
                stroke="none"
            />
            <circle
                cx="14.5"
                cy="12.5"
                r="1"
                fill="currentColor"
                stroke="none"
            />
        </template>
        <template v-else-if="name === 'email'">
            <rect x="3" y="5" width="18" height="14" />
            <path d="m3 7 9 6 9-6" />
        </template>
        <template v-else-if="name === 'digest'">
            <rect x="3" y="5" width="13" height="14" />
            <path d="M7 9h5M7 13h5" />
            <circle cx="18" cy="17" r="3" />
            <path d="M18 15.5V17l1 1" />
        </template>
        <template v-else-if="name === 'clock'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </template>
        <template v-else-if="name === 'shield'">
            <path d="M12 3 5 6v5c0 4.5 2.7 8 7 10 4.3-2 7-5.5 7-10V6l-7-3Z" />
            <path d="m9 12 2 2 4-4" />
        </template>
        <template v-else-if="name === 'check'">
            <path d="m5 12 4 4L19 6" />
        </template>
        <template v-else-if="name === 'chevron-down'">
            <path d="m6 9 6 6 6-6" />
        </template>
        <template v-else-if="name === 'chevron-right'">
            <path d="m9 18 6-6-6-6" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="8" />
        </template>
    </svg>
</template>
