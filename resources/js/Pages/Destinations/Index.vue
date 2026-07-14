<script setup lang="ts">
import AppSelect from '@/Components/App/AppSelect.vue';
import ChoiceCards from '@/Components/App/ChoiceCards.vue';
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import ToggleSwitch from '@/Components/App/ToggleSwitch.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { relativeDate } from '@/lib/format';
import { browserTimezone, timezoneOptions } from '@/lib/timezones';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Destination {
    id: number;
    name: string;
    type: string;
    enabled: boolean;
    summary: string;
    config: any;
    connections_count: number;
    last_delivered_at?: string;
}
const props = defineProps<{
    project: { slug: string; name: string };
    destinations: Destination[];
}>();
const page = usePage<any>();
const open = ref(false);
const editing = ref<Destination | null>(null);
const recipients = ref('');
const headers = ref('{}');
const blank = () => ({
    name: '',
    type: 'webhook',
    enabled: true,
    config: {
        url: '',
        method: 'POST',
        headers: {},
        signing_secret: '',
        username: 'Hookroute',
        recipients: [],
        send_time: '18:00',
        window_start_time: '08:00',
        timezone: browserTimezone(),
        subject: 'Daily event digest',
        send_empty: false,
    },
});
const form = useForm<any>(blank());
function add() {
    editing.value = null;
    form.defaults(blank());
    form.reset();
    recipients.value = '';
    headers.value = '{}';
    open.value = true;
}
function edit(d: Destination) {
    editing.value = d;
    form.name = d.name;
    form.type = d.type;
    form.enabled = d.enabled;
    form.config = { ...blank().config, ...(d.config || {}) };
    recipients.value = (d.config?.recipients || []).join(', ');
    headers.value = JSON.stringify(d.config?.headers || {}, null, 2);
    open.value = true;
}
function submit() {
    try {
        form.config.headers = JSON.parse(headers.value || '{}');
    } catch {
        form.setError('config.headers', 'Headers must be a valid JSON object.');
        return;
    }
    form.config.recipients = recipients.value
        .split(',')
        .map((x) => x.trim())
        .filter(Boolean);
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.clearErrors();
        },
    };
    editing.value
        ? form.patch(
              route('projects.destinations.update', [
                  props.project.slug,
                  editing.value.id,
              ]),
              options,
          )
        : form.post(
              route('projects.destinations.store', props.project.slug),
              options,
          );
}
function remove(d: Destination) {
    if (confirm(`Delete ${d.name}? Its routes will also be removed.`))
        router.delete(
            route('projects.destinations.destroy', [props.project.slug, d.id]),
        );
}
const labels: Record<string, string> = {
    webhook: 'HTTP webhook',
    discord: 'Discord',
    email: 'Immediate email',
    digest: 'Email digest',
};
const typeOptions = [
    {
        value: 'webhook',
        label: 'HTTP webhook',
        description: 'Send a signed HTTPS request',
        icon: 'webhook',
    },
    {
        value: 'discord',
        label: 'Discord',
        description: 'Post into a Discord channel',
        icon: 'discord',
    },
    {
        value: 'email',
        label: 'Email',
        description: 'Send every event immediately',
        icon: 'email',
    },
    {
        value: 'digest',
        label: 'Email digest',
        description: 'Collect events into a schedule',
        icon: 'digest',
    },
];
const methodOptions = ['POST', 'PUT', 'PATCH'].map((method) => ({
    value: method,
    label: method,
}));
const timezones = timezoneOptions();
</script>
<template>
    <Head title="Destinations" /><AppShell>
        <PageHeader
            eyebrow="Egress"
            title="Destinations"
            description="Reusable endpoints hold credentials and transport settings. Message shape and filtering stay with each route."
            ><button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-primary"
                @click="add"
            >
                ＋ New destination
            </button></PageHeader
        >
        <div v-if="destinations.length" class="resource-grid">
            <article
                v-for="d in destinations"
                :key="d.id"
                class="resource-card"
                :class="{ clickable: page.props.currentProject.can_manage }"
            >
                <button
                    v-if="page.props.currentProject.can_manage"
                    type="button"
                    class="card-click-target"
                    :aria-label="`Edit ${d.name}`"
                    @click="edit(d)"
                />
                <div class="resource-actions">
                    <span
                        class="status"
                        :class="d.enabled ? 'delivered' : 'failed'"
                        >{{ d.enabled ? 'active' : 'paused' }}</span
                    >
                </div>
                <span class="resource-kind">{{ labels[d.type] }}</span>
                <h3>{{ d.name }}</h3>
                <p class="mono muted truncate">{{ d.summary }}</p>
                <div class="resource-meta">
                    <span>{{ d.connections_count }} routes</span
                    ><span>{{ relativeDate(d.last_delivered_at) }}</span>
                </div>
                <div
                    v-if="page.props.currentProject.can_manage"
                    class="page-actions"
                    style="margin-top: 14px"
                >
                    <button
                        class="btn btn-small btn-danger"
                        @click.stop="remove(d)"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>
        <div v-else class="empty">
            <div class="empty-mark">◎</div>
            <h3>No place to deliver yet</h3>
            <p>
                Add a generic HTTPS endpoint, Discord webhook, immediate email
                address or a daily aggregate email.
            </p>
            <button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-primary"
                @click="add"
            >
                Add destination
            </button>
        </div>
        <Dialog
            v-if="open"
            :title="editing ? 'Edit destination' : 'New destination'"
            @close="open = false"
            ><form autocomplete="off" @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field full">
                        <label>Name</label
                        ><input
                            v-model="form.name"
                            class="input"
                            autofocus
                            placeholder="Operations Discord"
                        />
                        <div v-if="form.errors.name" class="field-error">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div class="field full">
                        <label>Type</label
                        ><ChoiceCards
                            v-model="form.type"
                            :options="typeOptions"
                            :disabled="!!editing"
                        />
                        <div v-if="form.errors.type" class="field-error">
                            {{ form.errors.type }}
                        </div>
                    </div>
                    <template v-if="form.type === 'webhook'"
                        ><div class="field full">
                            <label>HTTPS URL</label
                            ><input
                                v-model="form.config.url"
                                class="input"
                                autocomplete="off"
                                placeholder="https://example.com/webhooks"
                            />
                            <div
                                v-if="form.errors['config.url']"
                                class="field-error"
                            >
                                {{ form.errors['config.url'] }}
                            </div>
                        </div>
                        <div class="field full">
                            <label>Method</label
                            ><ChoiceCards
                                v-model="form.config.method"
                                :options="methodOptions"
                                compact
                            />
                        </div>
                        <div class="field">
                            <label>Signing secret · optional</label
                            ><input
                                v-model="form.config.signing_secret"
                                class="input"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="
                                    editing
                                        ? 'Leave blank to keep current secret'
                                        : 'At least 16 characters'
                                "
                            />
                        </div>
                        <div class="field full">
                            <label>Static headers · JSON</label
                            ><textarea
                                v-model="headers"
                                class="textarea"
                                style="min-height: 90px"
                                placeholder='{"X-Api-Key":"…"}'
                            />
                            <div
                                v-if="form.errors['config.headers']"
                                class="field-error"
                            >
                                {{ form.errors['config.headers'] }}
                            </div>
                        </div></template
                    >
                    <template v-if="form.type === 'discord'"
                        ><div class="field full">
                            <label>Discord webhook URL</label
                            ><input
                                v-model="form.config.url"
                                class="input"
                                type="password"
                                autocomplete="new-password"
                                placeholder="https://discord.com/api/webhooks/…"
                            />
                            <div
                                v-if="form.errors['config.url']"
                                class="field-error"
                            >
                                {{ form.errors['config.url'] }}
                            </div>
                        </div>
                        <div class="field full">
                            <label>Displayed username</label
                            ><input
                                v-model="form.config.username"
                                class="input"
                                placeholder="Hookroute"
                            /></div
                    ></template>
                    <template
                        v-if="form.type === 'email' || form.type === 'digest'"
                        ><div class="field full">
                            <label>Recipients · comma separated</label
                            ><input
                                v-model="recipients"
                                class="input"
                                placeholder="ops@example.com, audit@example.com"
                            />
                            <div
                                v-if="
                                    form.errors['config.recipients'] ||
                                    form.errors['config.recipients.0']
                                "
                                class="field-error"
                            >
                                {{
                                    form.errors['config.recipients'] ||
                                    form.errors['config.recipients.0']
                                }}
                            </div>
                        </div></template
                    >
                    <template v-if="form.type === 'digest'"
                        ><div class="field">
                            <label>Send at</label
                            ><input
                                v-model="form.config.send_time"
                                type="time"
                                class="input"
                            />
                        </div>
                        <div class="field">
                            <label>Include events since</label
                            ><input
                                v-model="form.config.window_start_time"
                                type="time"
                                class="input"
                            />
                        </div>
                        <div class="field">
                            <label>Timezone</label
                            ><AppSelect
                                v-model="form.config.timezone"
                                :options="timezones"
                                searchable
                            />
                        </div>
                        <div class="field">
                            <label>Subject</label
                            ><input
                                v-model="form.config.subject"
                                class="input"
                            />
                        </div>
                        <div class="field full">
                            <ToggleSwitch
                                v-model="form.config.send_empty"
                                label="Send empty digests"
                                description="Send the scheduled email even when no event matched the window."
                            /></div
                    ></template>
                    <div class="field full">
                        <ToggleSwitch
                            v-model="form.enabled"
                            label="Destination is active"
                            description="Paused destinations keep their configuration but receive no deliveries."
                        />
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" @click="open = false">
                        Cancel</button
                    ><button
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        {{ editing ? 'Save changes' : 'Create destination' }}
                    </button>
                </div>
            </form></Dialog
        >
    </AppShell>
</template>
