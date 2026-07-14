<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';

const emit = defineEmits<{ close: [] }>();
defineProps<{ title: string }>();

function onKey(event: KeyboardEvent) {
    if (event.key === 'Escape') emit('close');
}
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div class="dialog-backdrop" @mousedown.self="emit('close')">
            <section
                class="dialog"
                role="dialog"
                aria-modal="true"
                :aria-label="title"
            >
                <header class="dialog-head">
                    <h2>{{ title }}</h2>
                    <button
                        class="icon-button"
                        type="button"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        ×
                    </button>
                </header>
                <div class="dialog-body"><slot /></div>
            </section>
        </div>
    </Teleport>
</template>
