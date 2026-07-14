<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
defineProps<{ canRegister: boolean }>();
const page = usePage<any>();
</script>

<template>
    <Head title="Webhook routing without the machinery" />
    <div class="welcome">
        <nav class="welcome-nav">
            <Link href="/" class="brand" style="padding: 0; color: var(--ink)"
                ><span
                    class="brand-mark"
                    style="border-color: var(--ink)"
                /><span class="brand-word">hookroute</span></Link
            >
            <div class="page-actions">
                <Link
                    v-if="page.props.auth.user"
                    :href="route('dashboard')"
                    class="btn btn-primary"
                    >Open console →</Link
                >
                <template v-else
                    ><Link :href="route('login')" class="btn">Sign in</Link
                    ><Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="btn btn-primary"
                        >Start routing →</Link
                    ></template
                >
            </div>
        </nav>
        <main class="welcome-hero">
            <section class="welcome-copy">
                <span class="eyebrow">A focused webhook gateway</span>
                <h1>Receive once.<em>Route clearly.</em></h1>
                <p>
                    Capture, inspect, reshape and deliver events to every system
                    that needs them. Reliable fan-out, replay and email
                    digests—without running a workflow factory.
                </p>
                <div class="page-actions">
                    <Link
                        :href="
                            page.props.auth.user
                                ? route('dashboard')
                                : canRegister
                                  ? route('register')
                                  : route('login')
                        "
                        class="btn btn-primary"
                        >Create your first source</Link
                    ><a href="#why" class="btn btn-soft">See the model ↓</a>
                </div>
            </section>
            <aside id="why" class="welcome-side">
                <article class="feature-slab">
                    <span class="eyebrow">01 / Capture</span
                    ><strong>Every request has a paper trail.</strong>
                    <p>
                        Raw payload retention, redacted headers, source secrets,
                        HMAC verification and idempotency from the first byte.
                    </p>
                </article>
                <article class="feature-slab">
                    <span class="eyebrow" style="color: #526059"
                        >02 / Route</span
                    ><strong>One source. Any number of outcomes.</strong>
                    <p>
                        Filter on payload values, preserve the original body or
                        render an explicit per-route template.
                    </p>
                </article>
                <article class="feature-slab">
                    <span class="eyebrow">03 / Deliver</span
                    ><strong>HTTP, Discord and email.</strong>
                    <p>
                        Independent retries, response excerpts, one-click
                        replay, immediate email or a scheduled daily digest
                        window.
                    </p>
                </article>
            </aside>
        </main>
    </div>
</template>
