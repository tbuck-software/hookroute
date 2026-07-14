<script setup lang="ts">
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { relativeDate } from '@/lib/format';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
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
const filter = reactive({
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
                    ><select
                        v-model="filter.source"
                        class="select"
                        @change="apply"
                    >
                        <option value="">All sources</option>
                        <option v-for="s in sources" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                </div>
                <div class="field">
                    <label>Delivery state</label
                    ><select
                        v-model="filter.status"
                        class="select"
                        @change="apply"
                    >
                        <option value="">Any state</option>
                        <option value="delivered">Delivered</option>
                        <option value="retrying">Retrying</option>
                        <option value="failed">Failed</option>
                        <option value="pending">Pending</option>
                    </select>
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
                    <tr v-for="e in events.data" :key="e.id">
                        <td style="white-space: nowrap">
                            <strong>{{ relativeDate(e.received_at) }}</strong>
                            <div class="mono muted">
                                {{ new Date(e.received_at).toLocaleString() }}
                            </div>
                        </td>
                        <td>
                            <Link
                                :href="
                                    route('projects.events.show', [
                                        project.slug,
                                        e.id,
                                    ])
                                "
                                ><strong>{{ e.source.name }}</strong>
                                <div class="mono muted">{{ e.id }}</div></Link
                            >
                        </td>
                        <td class="mono muted">
                            <Link
                                :href="
                                    route('projects.events.show', [
                                        project.slug,
                                        e.id,
                                    ])
                                "
                                ><div style="max-width: 520px" class="truncate">
                                    {{ e.payload_preview }}
                                </div></Link
                            >
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
