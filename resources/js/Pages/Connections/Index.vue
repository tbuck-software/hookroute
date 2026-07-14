<script setup lang="ts">
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
interface Filter {
    field: string;
    operator: string;
    value: any;
}
interface Connection {
    id: number;
    name: string;
    source_id: number;
    destination_id: number;
    enabled: boolean;
    payload_mode: string;
    subject_template?: string;
    body_template?: string;
    filters: Filter[];
    source: { name: string };
    destination: { name: string; type: string };
}
const props = defineProps<{
    project: { slug: string };
    connections: Connection[];
    sources: Array<{ id: number; name: string }>;
    destinations: Array<{ id: number; name: string; type: string }>;
}>();
const page = usePage<any>();
const open = ref(false);
const editing = ref<Connection | null>(null);
const blank = () => ({
    name: '',
    source_id: props.sources[0]?.id || null,
    destination_id: props.destinations[0]?.id || null,
    enabled: true,
    payload_mode: 'passthrough',
    subject_template: '',
    body_template: '',
    filters: [] as Filter[],
});
const form = useForm<any>(blank());
function add() {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    open.value = true;
}
function edit(c: Connection) {
    editing.value = c;
    form.name = c.name;
    form.source_id = c.source_id;
    form.destination_id = c.destination_id;
    form.enabled = c.enabled;
    form.payload_mode = c.payload_mode;
    form.subject_template = c.subject_template || '';
    form.body_template = c.body_template || '';
    form.filters = (c.filters || []).map((x) => ({ ...x }));
    open.value = true;
}
function addFilter() {
    form.filters.push({ field: '', operator: 'equals', value: '' });
}
function submit() {
    const o = { preserveScroll: true, onSuccess: () => (open.value = false) };
    editing.value
        ? form.patch(
              route('projects.connections.update', [
                  props.project.slug,
                  editing.value.id,
              ]),
              o,
          )
        : form.post(route('projects.connections.store', props.project.slug), o);
}
function remove(c: Connection) {
    if (confirm(`Delete route ${c.name}?`))
        router.delete(
            route('projects.connections.destroy', [props.project.slug, c.id]),
        );
}
const typeLabel: Record<string, string> = {
    webhook: 'HTTP',
    discord: 'Discord',
    email: 'Email',
    digest: 'Digest',
};
</script>
<template>
    <Head title="Routes" /><AppShell
        ><PageHeader
            eyebrow="Routing graph"
            title="Routes"
            description="Connect one source to one destination, then add optional AND-filters and a destination-specific body template."
            ><button
                v-if="
                    page.props.currentProject.can_manage &&
                    sources.length &&
                    destinations.length
                "
                class="btn btn-primary"
                @click="add"
            >
                ＋ New route
            </button></PageHeader
        >
        <div v-if="connections.length" class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Source</th>
                        <th></th>
                        <th>Destination</th>
                        <th>Filters</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in connections" :key="c.id">
                        <td>
                            <strong>{{ c.name }}</strong>
                            <div class="mono muted">{{ c.payload_mode }}</div>
                        </td>
                        <td>{{ c.source.name }}</td>
                        <td class="mono muted">→</td>
                        <td>
                            <span class="resource-kind">{{
                                typeLabel[c.destination.type]
                            }}</span>
                            {{ c.destination.name }}
                        </td>
                        <td>{{ c.filters?.length || 0 }} conditions</td>
                        <td>
                            <span
                                class="status"
                                :class="c.enabled ? 'delivered' : 'failed'"
                                >{{ c.enabled ? 'active' : 'paused' }}</span
                            >
                        </td>
                        <td>
                            <div
                                v-if="page.props.currentProject.can_manage"
                                class="table-actions"
                            >
                                <button
                                    class="btn btn-small btn-soft"
                                    @click="edit(c)"
                                >
                                    Edit</button
                                ><button
                                    class="btn btn-small btn-danger"
                                    @click="remove(c)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="empty">
            <div class="empty-mark">→</div>
            <h3>No routes wired</h3>
            <p v-if="sources.length && destinations.length">
                Choose what listens, where it goes, and which payloads are
                allowed through.
            </p>
            <p v-else>
                You need at least one source and one destination before a route
                can be created.
            </p>
            <button
                v-if="
                    page.props.currentProject.can_manage &&
                    sources.length &&
                    destinations.length
                "
                class="btn btn-primary"
                @click="add"
            >
                Wire first route
            </button>
        </div>
        <Dialog
            v-if="open"
            :title="editing ? 'Edit route' : 'New route'"
            @close="open = false"
            ><form @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field full">
                        <label>Route name</label
                        ><input
                            v-model="form.name"
                            class="input"
                            autofocus
                            placeholder="Orders → operations"
                        />
                        <div v-if="form.errors.name" class="field-error">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div class="field">
                        <label>Source</label
                        ><select v-model="form.source_id" class="select">
                            <option
                                v-for="s in sources"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Destination</label
                        ><select v-model="form.destination_id" class="select">
                            <option
                                v-for="d in destinations"
                                :key="d.id"
                                :value="d.id"
                            >
                                {{ d.name }} · {{ typeLabel[d.type] }}
                            </option>
                        </select>
                    </div>
                    <div class="field full">
                        <label>Payload mode</label
                        ><select v-model="form.payload_mode" class="select">
                            <option value="passthrough">
                                Pass through raw request body
                            </option>
                            <option value="template">
                                Render a route template
                            </option>
                        </select>
                    </div>
                    <template v-if="form.payload_mode === 'template'"
                        ><div class="field full">
                            <label>Subject template · email only</label
                            ><input
                                v-model="form.subject_template"
                                class="input"
                                placeholder="Event {{ event.id }} from {{ source.name }}"
                            />
                        </div>
                        <div class="field full">
                            <label>Body template</label
                            ><textarea
                                v-model="form.body_template"
                                class="textarea"
                                placeholder='{"event":"{{ event.id }}","payload":{{ payload }}}'
                            />
                            <div class="field-hint">
                                Available roots: payload, event, source and
                                project. Generic HTTP templates should produce
                                valid JSON.
                            </div>
                            <div
                                v-if="form.errors.body_template"
                                class="field-error"
                            >
                                {{ form.errors.body_template }}
                            </div>
                        </div></template
                    >
                    <div class="field full">
                        <div
                            style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 8px;
                            "
                        >
                            <label style="margin: 0"
                                >Filters · all must match</label
                            ><button
                                type="button"
                                class="btn btn-small"
                                @click="addFilter"
                            >
                                ＋ Condition
                            </button>
                        </div>
                        <div
                            v-for="(filter, i) in form.filters"
                            :key="i"
                            class="filter-row"
                        >
                            <input
                                v-model="filter.field"
                                class="input"
                                placeholder="order.status"
                            /><select v-model="filter.operator" class="select">
                                <option value="equals">equals</option>
                                <option value="not_equals">
                                    does not equal
                                </option>
                                <option value="contains">contains</option>
                                <option value="exists">exists</option>
                                <option value="not_exists">
                                    does not exist
                                </option>
                                <option value="greater_than">
                                    greater than
                                </option>
                                <option value="less_than">
                                    less than
                                </option></select
                            ><input
                                v-if="
                                    !['exists', 'not_exists'].includes(
                                        filter.operator,
                                    )
                                "
                                v-model="filter.value"
                                class="input"
                                placeholder="paid"
                            /><span v-else></span
                            ><button
                                type="button"
                                class="btn btn-danger"
                                @click="form.filters.splice(i, 1)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <label class="check-row field full"
                        ><input v-model="form.enabled" type="checkbox" /> Route
                        is active</label
                    >
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" @click="open = false">
                        Cancel</button
                    ><button
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        {{ editing ? 'Save changes' : 'Create route' }}
                    </button>
                </div>
            </form></Dialog
        >
    </AppShell>
</template>
