<script setup lang="ts">
defineProps<{ label: string; description?: string; disabled?: boolean }>();
const model = defineModel<boolean>({ required: true });
</script>

<template>
    <button
        type="button"
        class="toggle-row"
        :class="{ active: model }"
        role="switch"
        :aria-checked="model"
        :disabled="disabled"
        @click="model = !model"
    >
        <span class="toggle-track"><span class="toggle-thumb" /></span>
        <span class="toggle-copy">
            <strong>{{ label }}</strong>
            <small v-if="description">{{ description }}</small>
        </span>
    </button>
</template>
