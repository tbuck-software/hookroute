<script setup lang="ts">
import AppIcon from '@/Components/App/AppIcon.vue';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

export interface SelectOption {
    value: string | number;
    label: string;
    description?: string;
    icon?: string;
}

const props = withDefaults(
    defineProps<{
        options: SelectOption[];
        placeholder?: string;
        disabled?: boolean;
        searchable?: boolean;
        compact?: boolean;
    }>(),
    {
        placeholder: 'Choose an option',
        disabled: false,
        searchable: false,
        compact: false,
    },
);
const model = defineModel<string | number | null>();
const root = ref<HTMLElement | null>(null);
const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const query = ref('');
const activeIndex = ref(0);

const selected = computed(() =>
    props.options.find((option) => option.value === model.value),
);
const filteredOptions = computed(() => {
    const needle = query.value.trim().toLocaleLowerCase();
    if (!needle) return props.options;

    return props.options.filter((option) =>
        `${option.label} ${option.description || ''}`
            .toLocaleLowerCase()
            .includes(needle),
    );
});

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
}

function choose(option: SelectOption) {
    model.value = option.value;
    open.value = false;
}

function move(direction: number) {
    if (!open.value) {
        open.value = true;
        return;
    }
    const count = filteredOptions.value.length;
    if (!count) return;
    activeIndex.value = (activeIndex.value + direction + count) % count;
}

function chooseActive() {
    const option = filteredOptions.value[activeIndex.value];
    if (open.value && option) choose(option);
    else toggle();
}

function onDocumentPointer(event: PointerEvent) {
    if (!root.value?.contains(event.target as Node)) open.value = false;
}

watch(open, async (isOpen) => {
    if (!isOpen) {
        query.value = '';
        return;
    }
    activeIndex.value = Math.max(
        0,
        filteredOptions.value.findIndex(
            (option) => option.value === model.value,
        ),
    );
    if (props.searchable) {
        await nextTick();
        searchInput.value?.focus();
    }
});
watch(filteredOptions, () => (activeIndex.value = 0));
onMounted(() => document.addEventListener('pointerdown', onDocumentPointer));
onBeforeUnmount(() =>
    document.removeEventListener('pointerdown', onDocumentPointer),
);
</script>

<template>
    <div
        ref="root"
        class="app-select"
        :class="{ open, compact, disabled }"
        @keydown.esc="open = false"
    >
        <button
            type="button"
            class="app-select-trigger"
            :disabled="disabled"
            aria-haspopup="listbox"
            :aria-expanded="open"
            @click="toggle"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="chooseActive"
            @keydown.space.prevent="chooseActive"
        >
            <span v-if="selected" class="app-select-value">
                <span v-if="selected.icon" class="select-icon"
                    ><AppIcon :name="selected.icon" :size="16"
                /></span>
                <span>
                    <strong>{{ selected.label }}</strong>
                    <small v-if="selected.description">{{
                        selected.description
                    }}</small>
                </span>
            </span>
            <span v-else class="muted">{{ placeholder }}</span>
            <AppIcon name="chevron-down" :size="15" />
        </button>
        <div v-if="open" class="app-select-popover">
            <input
                v-if="searchable"
                ref="searchInput"
                v-model="query"
                class="app-select-search"
                type="search"
                placeholder="Search…"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="chooseActive"
            />
            <div class="app-select-options" role="listbox">
                <button
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    type="button"
                    class="app-select-option"
                    :class="{
                        selected: option.value === model,
                        active: index === activeIndex,
                    }"
                    role="option"
                    :aria-selected="option.value === model"
                    @mouseenter="activeIndex = index"
                    @click="choose(option)"
                >
                    <span v-if="option.icon" class="select-icon"
                        ><AppIcon :name="option.icon" :size="16"
                    /></span>
                    <span class="app-select-option-copy">
                        <strong>{{ option.label }}</strong>
                        <small v-if="option.description">{{
                            option.description
                        }}</small>
                    </span>
                    <AppIcon
                        v-if="option.value === model"
                        name="check"
                        :size="15"
                    />
                </button>
                <div v-if="!filteredOptions.length" class="app-select-empty">
                    No matching options
                </div>
            </div>
        </div>
    </div>
</template>
