<script setup lang="ts">
import AppSelect from '@/Components/App/AppSelect.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { relativeDate } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
interface EventRow {
    id: string;
    source: { id: number; name: string };
    received_at: string;
    content_type?: string;
    delivery_counts: Record<string, number>;
    payload_preview: string;
}
const props = defineProps<{
    project: { slug: string };
    events: {
        data: EventRow[];
        links: Array<{ url?: string; label: string; active: boolean }>;
    };
    sources: Array<{ id: number; name: string }>;
    filters: { source?: string; status?: string };
}>();
const filter = reactive<{ source: string | number; status: string }>({
    source: props.filters.source || '',
    status: props.filters.status || '',
});
function apply() {
    router.get(route('projects.events.index', props.project.slug), filter, {
        preserveState: true,
        replace: true,
    });
}
function count(e: EventRow, s: string) {
    return e.delivery_counts?.[s] || 0;
}
function show(e: EventRow) {
    router.visit(route('projects.events.show', [props.project.slug, e.id]));
}
const sourceOptions = computed(() => [
    { value: '', label: 'All sources', icon: 'source' },
    ...props.sources.map((source) => ({
        value: source.id,
        label: source.name,
        icon: 'source',
    })),
]);
const statusOptions = [
    { value: '', label: 'Any state', icon: 'event' },
    { value: 'delivered', label: 'Delivered', icon: 'check' },
    { value: 'retrying', label: 'Retrying', icon: 'clock' },
    { value: 'failed', label: 'Failed', icon: 'event' },
    { value: 'pending', label: 'Pending', icon: 'clock' },
];
</script>
<template>
    <Head title="Events" /><AppShell
        ><PageHeader
            eyebrow="Captured traffic"
            title="Events"
            description="Inspect the immutable inbound record and every independent downstream attempt."
        />
        <div class="panel" style="margin-bottom: 16px">
            <div class="panel-body form-grid">
                <div class="field">
                    <label>Source</label
                    ><AppSelect
                        v-model="filter.source"
                        :options="sourceOptions"
                        @update:model-value="apply"
                    />
                </div>
                <div class="field">
                    <label>Delivery state</label
                    ><AppSelect
                        v-model="filter.status"
                        :options="statusOptions"
                        @update:model-value="apply"
                    />
                </div>
            </div>
        </div>
        <div v-if="events.data.length" class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Received</th>
                        <th>Source / ID</th>
                        <th>Payload</th>
                        <th>Deliveries</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="e in events.data"
                        :key="e.id"
                        class="row-clickable"
                        tabindex="0"
                        @click="show(e)"
                        @keydown.enter="show(e)"
                    >
                        <td style="white-space: nowrap">
                            <strong>{{ relativeDate(e.received_at) }}</strong>
                            <div class="mono muted">
                                {{ new Date(e.received_at).toLocaleString() }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ e.source.name }}</strong>
                            <div class="mono muted">{{ e.id }}</div>
                        </td>
                        <td class="mono muted">
                            <div style="max-width: 520px" class="truncate">
                                {{ e.payload_preview }}
                            </div>
                        </td>
                        <td>
                            <div class="page-actions">
                                <span
                                    v-if="count(e, 'delivered')"
                                    class="status delivered"
                                    >{{ count(e, 'delivered') }} delivered</span
                                ><span
                                    v-if="count(e, 'retrying')"
                                    class="status retrying"
                                    >{{ count(e, 'retrying') }} retrying</span
                                ><span
                                    v-if="count(e, 'failed')"
                                    class="status failed"
                                    >{{ count(e, 'failed') }} failed</span
                                ><span
                                    v-if="
                                        !Object.keys(e.delivery_counts || {})
                                            .length
                                    "
                                    class="status"
                                    >no route</span
                                >
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="empty">
            <div class="empty-mark">∅</div>
            <h3>No matching events</h3>
            <p>
                Once a source receives traffic, the raw request and its delivery
                fan-out will be visible here.
            </p>
        </div>
        <div v-if="events.links.length > 3" class="pagination">
            <component
                :is="link.url ? Link : 'span'"
                v-for="link in events.links"
                :key="link.label"
                :href="link.url"
                :class="{ active: link.active }"
            >
                <span v-html="link.label" />
            </component>
        </div>
    </AppShell>
</template>
import AppSelect from '@/Components/App/AppSelect.vue';
