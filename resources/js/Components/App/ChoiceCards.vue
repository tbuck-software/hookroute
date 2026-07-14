<script setup lang="ts">
import AppIcon from '@/Components/App/AppIcon.vue';
import type { SelectOption } from '@/Components/App/AppSelect.vue';

withDefaults(
    defineProps<{
        options: SelectOption[];
        disabled?: boolean;
        compact?: boolean;
    }>(),
    { disabled: false, compact: false },
);
const model = defineModel<string | number | null>();
</script>

<template>
    <div
        class="choice-grid"
        :class="{ compact }"
        role="radiogroup"
        :style="{ '--choice-count': options.length }"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="choice-card"
            :class="{ selected: option.value === model }"
            role="radio"
            :aria-checked="option.value === model"
            :disabled="disabled"
            @click="model = option.value"
        >
            <span v-if="option.icon" class="choice-icon"
                ><AppIcon :name="option.icon" :size="compact ? 16 : 19"
            /></span>
            <span>
                <strong>{{ option.label }}</strong>
                <small v-if="option.description">{{
                    option.description
                }}</small>
            </span>
        </button>
    </div>
</template>
