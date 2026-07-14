<script setup lang="ts">
import PageHeader from '@/Components/App/PageHeader.vue';
import StatusBadge from '@/Components/App/StatusBadge.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { formatDate } from '@/lib/format';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
const props = defineProps<{ project: { slug: string }; event: any }>();
const page = usePage<any>();
function replay(d: any) {
    router.post(
        route('projects.deliveries.replay', [
            props.project.slug,
            props.event.public_id,
            d.id,
        ]),
        {},
        { preserveScroll: true },
    );
}
const pretty = (v: any) => JSON.stringify(v, null, 2);
</script>
<template>
    <Head :title="event.public_id" /><AppShell
        ><PageHeader
            :eyebrow="event.source.name"
            :title="event.public_id"
            :description="`Received ${formatDate(event.received_at)} · ${event.method} · ${event.content_type || 'unknown content type'}`"
            ><Link
                :href="route('projects.events.index', project.slug)"
                class="btn btn-soft"
                >← Event stream</Link
            ></PageHeader
        >
        <div class="detail-grid">
            <section class="stack">
                <div class="panel">
                    <header class="panel-head">
                        <h2>Parsed payload</h2>
                        <span class="section-label">JSON view</span>
                    </header>
                    <pre class="code-block" style="border: 0">{{
                        pretty(event.payload)
                    }}</pre>
                </div>
                <div class="panel">
                    <header class="panel-head">
                        <h2>Raw request body</h2>
                        <span class="section-label">Immutable capture</span>
                    </header>
                    <pre class="code-block" style="border: 0">{{
                        event.raw_body
                    }}</pre>
                </div>
                <div class="panel">
                    <header class="panel-head">
                        <h2>Retained headers</h2>
                        <span class="section-label">Secrets redacted</span>
                    </header>
                    <pre class="code-block" style="border: 0">{{
                        pretty(event.headers)
                    }}</pre>
                </div>
            </section>
            <aside class="stack">
                <div class="panel">
                    <header class="panel-head">
                        <h2>Deliveries</h2>
                        <span class="section-label"
                            >{{ event.deliveries.length }} attempts</span
                        >
                    </header>
                    <div v-if="event.deliveries.length" class="event-list">
                        <article
                            v-for="d in event.deliveries"
                            :key="d.id"
                            style="
                                padding: 16px 18px;
                                border-bottom: 1px solid var(--line);
                            "
                        >
                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 12px;
                                "
                            >
                                <div>
                                    <strong style="font-size: 13px">{{
                                        d.destination.name
                                    }}</strong>
                                    <div class="mono muted">
                                        {{ d.connection.name }}
                                    </div>
                                </div>
                                <StatusBadge :status="d.status" />
                            </div>
                            <div
                                class="mono muted"
                                style="margin-top: 12px; line-height: 1.7"
                            >
                                Attempts: {{ d.attempts
                                }}<br v-if="d.response_status" />
                                <template v-if="d.response_status"
                                    >HTTP: {{ d.response_status }}</template
                                ><br v-if="d.last_attempted_at" />
                                <template v-if="d.last_attempted_at"
                                    >Last try:
                                    {{
                                        formatDate(d.last_attempted_at)
                                    }}</template
                                >
                            </div>
                            <p
                                v-if="d.last_error"
                                style="font-size: 11px; color: var(--red)"
                            >
                                {{ d.last_error }}
                            </p>
                            <pre
                                v-if="d.response_excerpt"
                                class="code-block"
                                style="margin-top: 12px; max-height: 160px"
                                >{{ d.response_excerpt }}</pre>
                            <button
                                v-if="
                                    page.props.currentProject.can_manage &&
                                    ![
                                        'pending',
                                        'processing',
                                        'retrying',
                                    ].includes(d.status)
                                "
                                class="btn btn-small btn-soft"
                                style="margin-top: 12px"
                                @click="replay(d)"
                            >
                                Replay delivery
                            </button>
                        </article>
                    </div>
                    <div
                        v-else
                        class="panel-body muted"
                        style="font-size: 12px"
                    >
                        No active route matched this payload.
                    </div>
                </div>
                <div class="panel">
                    <header class="panel-head">
                        <h2>Capture metadata</h2>
                    </header>
                    <div class="panel-body mono" style="line-height: 2">
                        Source · {{ event.source.name }}<br />Method ·
                        {{ event.method }}<br />Content ·
                        {{ event.content_type || '—' }}<br />Idempotency ·
                        {{ event.idempotency_key || '—' }}
                    </div>
                </div>
            </aside>
        </div>
    </AppShell>
</template>
