<script setup lang="ts">
import PageHeader from '@/Components/App/PageHeader.vue';
import AppShell from '@/Layouts/AppShell.vue';
import { relativeDate } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';

interface EventItem {
    id: string;
    source: string;
    received_at: string;
    payload_preview: string;
    deliveries: Array<{ name: string; status: string }>;
}
defineProps<{
    project: { slug: string; name: string };
    metrics: Record<string, number>;
    recentEvents: EventItem[];
}>();
</script>

<template>
    <Head :title="project.name" />
    <AppShell>
        <PageHeader
            eyebrow="Live operations"
            :title="project.name"
            description="A compact view of ingress volume, route health and the latest captured events."
        >
            <Link
                :href="route('projects.events.index', project.slug)"
                class="btn btn-soft"
                >Inspect events</Link
            >
            <Link
                :href="route('projects.sources.index', project.slug)"
                class="btn btn-primary"
                >＋ New source</Link
            >
        </PageHeader>
        <section class="grid-metrics">
            <article class="metric">
                <div class="metric-label">Events · 24 hours</div>
                <div class="metric-value">{{ metrics.events_24h }}</div>
                <div class="metric-note">accepted payloads</div>
            </article>
            <article class="metric">
                <div class="metric-label">Delivery rate</div>
                <div class="metric-value">{{ metrics.delivery_rate }}%</div>
                <div class="metric-note">last 24 hours</div>
            </article>
            <article class="metric">
                <div class="metric-label">Active sources</div>
                <div class="metric-value">{{ metrics.active_sources }}</div>
                <div class="metric-note">public ingress points</div>
            </article>
            <article class="metric">
                <div class="metric-label">Active routes</div>
                <div class="metric-value">{{ metrics.active_routes }}</div>
                <div class="metric-note">source → destination</div>
            </article>
            <article class="metric">
                <div class="metric-label">Needs attention</div>
                <div class="metric-value">{{ metrics.failed_deliveries }}</div>
                <div class="metric-note">failed deliveries</div>
            </article>
        </section>
        <section class="dashboard-grid">
            <div class="panel">
                <header class="panel-head">
                    <h2>Latest event stream</h2>
                    <span class="section-label">Newest first</span>
                </header>
                <div v-if="recentEvents.length" class="event-list">
                    <Link
                        v-for="event in recentEvents"
                        :key="event.id"
                        :href="
                            route('projects.events.show', [
                                project.slug,
                                event.id,
                            ])
                        "
                        class="event-row"
                    >
                        <div>
                            <div class="event-source">{{ event.source }}</div>
                            <div class="mono muted">
                                {{ relativeDate(event.received_at) }}
                            </div>
                        </div>
                        <div class="truncate">
                            <div class="mono">{{ event.id }}</div>
                            <div class="event-preview mono truncate">
                                {{ event.payload_preview }}
                            </div>
                        </div>
                        <div
                            class="delivery-dots"
                            :title="`${event.deliveries.length} deliveries`"
                        >
                            <span
                                v-for="delivery in event.deliveries"
                                :key="delivery.name"
                                class="dot"
                                :class="delivery.status"
                            />
                        </div>
                    </Link>
                </div>
                <div v-else class="empty" style="border: 0">
                    <div class="empty-mark">↳</div>
                    <h3>Waiting for the first event</h3>
                    <p>
                        Create a source, copy its URL and send any HTTP payload.
                        It will appear here immediately.
                    </p>
                    <Link
                        :href="route('projects.sources.index', project.slug)"
                        class="btn btn-primary"
                        >Configure ingress</Link
                    >
                </div>
            </div>
            <aside class="stack">
                <div class="panel">
                    <header class="panel-head">
                        <h2>Runtime model</h2>
                        <span class="status delivered">cron ready</span>
                    </header>
                    <div class="panel-body">
                        <p
                            class="muted"
                            style="
                                margin-top: 0;
                                font-size: 13px;
                                line-height: 1.6;
                            "
                        >
                            One scheduler call per minute dispatches due digests
                            and drains queued deliveries. No Redis or resident
                            worker required.
                        </p>
                        <pre class="code-block">
* * * * * php artisan schedule:run</pre>
                    </div>
                </div>
                <div class="panel">
                    <header class="panel-head"><h2>Routing order</h2></header>
                    <div class="panel-body mono" style="line-height: 2.1">
                        01 · authenticate source<br />02 · persist raw event<br />03
                        · evaluate filters<br />04 · enqueue fan-out<br />05 ·
                        retry independently
                    </div>
                </div>
            </aside>
        </section>
    </AppShell>
</template>
