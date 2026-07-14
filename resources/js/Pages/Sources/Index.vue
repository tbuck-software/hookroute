<script setup lang="ts">
import Dialog from '@/Components/App/Dialog.vue';
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { relativeDate } from '@/lib/format';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Source {
    id: string;
    name: string;
    slug: string;
    enabled: boolean;
    webhook_url?: string;
    signature_header?: string;
    has_signing_secret: boolean;
    connections_count: number;
    events_count: number;
    last_received_at?: string;
}
const props = defineProps<{
    project: { slug: string; name: string };
    sources: Source[];
}>();
const page = usePage<any>();
const open = ref(false);
const editing = ref<Source | null>(null);
const copied = ref<string | null>(null);
const form = useForm({
    name: '',
    enabled: true,
    signing_secret: '',
    signature_header: 'X-Hookroute-Signature',
    clear_signing_secret: false,
});
function add() {
    editing.value = null;
    form.reset();
    form.name = '';
    form.enabled = true;
    form.signature_header = 'X-Hookroute-Signature';
    open.value = true;
}
function edit(source: Source) {
    editing.value = source;
    form.name = source.name;
    form.enabled = source.enabled;
    form.signing_secret = '';
    form.signature_header = source.signature_header || 'X-Hookroute-Signature';
    form.clear_signing_secret = false;
    open.value = true;
}
function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };
    editing.value
        ? form.patch(
              route('projects.sources.update', [
                  props.project.slug,
                  editing.value.id,
              ]),
              options,
          )
        : form.post(
              route('projects.sources.store', props.project.slug),
              options,
          );
}
async function copy(source: Source) {
    if (!source.webhook_url) return;
    await navigator.clipboard.writeText(source.webhook_url);
    copied.value = source.id;
    setTimeout(() => (copied.value = null), 1600);
}
function toggle(source: Source) {
    router.patch(
        route('projects.sources.update', [props.project.slug, source.id]),
        {
            name: source.name,
            enabled: !source.enabled,
            signing_secret: null,
            signature_header: source.signature_header,
        },
        { preserveScroll: true },
    );
}
function rotate(source: Source) {
    if (
        confirm(
            'Rotate this source URL? The current URL will stop working immediately.',
        )
    )
        router.post(
            route('projects.sources.rotate', [props.project.slug, source.id]),
            {},
            { preserveScroll: true },
        );
}
function remove(source: Source) {
    if (confirm(`Delete ${source.name} and all captured events?`))
        router.delete(
            route('projects.sources.destroy', [props.project.slug, source.id]),
        );
}
</script>
<template>
    <Head title="Sources" /><AppShell>
        <PageHeader
            eyebrow="Ingress"
            title="Sources"
            description="Each source has an unguessable endpoint. Add optional HMAC verification when the sender can sign its raw request body."
            ><button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-primary"
                @click="add"
            >
                ＋ New source
            </button></PageHeader
        >
        <div v-if="sources.length" class="resource-grid">
            <article
                v-for="source in sources"
                :key="source.id"
                class="resource-card"
            >
                <div class="resource-actions">
                    <span
                        class="status"
                        :class="source.enabled ? 'delivered' : 'failed'"
                        >{{ source.enabled ? 'active' : 'paused' }}</span
                    >
                </div>
                <span class="resource-kind">HTTP ingress</span>
                <h3>{{ source.name }}</h3>
                <div class="mono muted">{{ source.id }}</div>
                <div class="url-box">
                    <code>{{
                        source.webhook_url ||
                        'Secret URL hidden for read-only members'
                    }}</code
                    ><button
                        v-if="source.webhook_url"
                        class="btn btn-small"
                        @click="copy(source)"
                    >
                        {{ copied === source.id ? 'Copied' : 'Copy' }}
                    </button>
                </div>
                <div class="resource-meta">
                    <span
                        >{{ source.events_count }} events ·
                        {{ source.connections_count }} routes</span
                    ><span>{{ relativeDate(source.last_received_at) }}</span>
                </div>
                <div
                    v-if="page.props.currentProject.can_manage"
                    class="page-actions"
                    style="margin-top: 14px"
                >
                    <button
                        class="btn btn-small btn-soft"
                        @click="edit(source)"
                    >
                        Edit</button
                    ><button
                        class="btn btn-small btn-soft"
                        @click="toggle(source)"
                    >
                        {{ source.enabled ? 'Pause' : 'Resume' }}</button
                    ><button
                        class="btn btn-small btn-soft"
                        @click="rotate(source)"
                    >
                        Rotate URL</button
                    ><button
                        class="btn btn-small btn-danger"
                        @click="remove(source)"
                    >
                        Delete
                    </button>
                </div>
            </article>
        </div>
        <div v-else class="empty">
            <div class="empty-mark">↳</div>
            <h3>Nothing is listening yet</h3>
            <p>
                Create a source to receive JSON, form data or any raw HTTP body.
                The generated URL is ready immediately.
            </p>
            <button
                v-if="page.props.currentProject.can_manage"
                class="btn btn-primary"
                @click="add"
            >
                Create source
            </button>
        </div>
        <Dialog
            v-if="open"
            :title="editing ? 'Edit source' : 'Create a source'"
            @close="open = false"
            ><form @submit.prevent="submit">
                <div class="form-grid">
                    <div class="field full">
                        <label>Source name</label
                        ><input
                            v-model="form.name"
                            class="input"
                            autofocus
                            placeholder="GitHub production"
                        />
                        <div v-if="form.errors.name" class="field-error">
                            {{ form.errors.name }}
                        </div>
                    </div>
                    <div class="field">
                        <label>HMAC secret · optional</label
                        ><input
                            v-model="form.signing_secret"
                            type="password"
                            class="input"
                            :placeholder="
                                editing && editing.has_signing_secret
                                    ? 'Leave blank to keep current secret'
                                    : 'At least 16 characters'
                            "
                        />
                        <div class="field-hint">
                            Requests must carry an HMAC-SHA256 of the exact raw
                            body.
                        </div>
                        <div
                            v-if="form.errors.signing_secret"
                            class="field-error"
                        >
                            {{ form.errors.signing_secret }}
                        </div>
                    </div>
                    <div class="field">
                        <label>Signature header</label
                        ><input v-model="form.signature_header" class="input" />
                        <div
                            v-if="form.errors.signature_header"
                            class="field-error"
                        >
                            {{ form.errors.signature_header }}
                        </div>
                    </div>
                    <label
                        v-if="editing?.has_signing_secret"
                        class="check-row field full"
                        ><input
                            v-model="form.clear_signing_secret"
                            type="checkbox"
                        />
                        Remove existing HMAC verification</label
                    ><label v-if="editing" class="check-row field full"
                        ><input v-model="form.enabled" type="checkbox" /> Source
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
                        {{ editing ? 'Save changes' : 'Create source' }}
                    </button>
                </div>
            </form></Dialog
        >
    </AppShell>
</template>
