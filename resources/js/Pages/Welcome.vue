<script setup lang="ts">
import AppIcon from '@/Components/App/AppIcon.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps<{
    canRegister: boolean;
    repositoryUrl?: string;
}>();

const page = usePage<any>();
const contactEmail = ['mail', 'tbuck.de'].join('@');
const setupSubject = encodeURIComponent('Hookroute setup for 149 EUR');
</script>

<template>
    <Head title="Webhook routing without the machinery">
        <meta
            head-key="description"
            name="description"
            content="Receive, inspect, transform and fan out webhooks with a focused open-source Laravel gateway."
        />
    </Head>

    <PublicLayout :repository-url="repositoryUrl">
        <main>
            <section class="landing-hero">
                <div class="landing-hero-copy">
                    <span class="eyebrow landing-eyebrow"
                        >A focused webhook gateway</span
                    >
                    <h1>Receive once.<em>Route clearly.</em></h1>
                    <p class="landing-lede">
                        Capture, inspect, reshape and deliver events to every
                        system that needs them. Reliable fan-out, replay and
                        email digests—without running a workflow factory.
                    </p>
                    <div class="page-actions landing-actions">
                        <Link
                            :href="
                                page.props.auth.user
                                    ? route('dashboard')
                                    : canRegister
                                      ? route('register')
                                      : route('login')
                            "
                            class="btn btn-primary landing-primary-cta"
                        >
                            Create your first source →
                        </Link>
                        <a href="#model" class="btn btn-soft">
                            See how it works ↓
                        </a>
                    </div>
                    <div class="landing-tech-line" aria-label="Technology">
                        <span>Open source</span>
                        <span>Laravel + Vue</span>
                        <span>PHP 8.3+</span>
                        <span>MySQL or SQLite</span>
                    </div>
                </div>

                <div class="signal-board" aria-label="Webhook routing example">
                    <div class="signal-board-head">
                        <span class="live-dot" />
                        <span>LIVE EVENT / EVT_9F31</span>
                        <span>12:04:18.241</span>
                    </div>
                    <div class="signal-source">
                        <span class="signal-icon"
                            ><AppIcon name="source" :size="22"
                        /></span>
                        <div>
                            <small>INCOMING</small>
                            <strong>POST /hooks/orders</strong>
                            <code>{ "event": "order.paid" }</code>
                        </div>
                        <span class="signal-status">VERIFIED</span>
                    </div>
                    <div class="signal-path" aria-hidden="true">
                        <span />
                        <strong>filter + transform</strong>
                        <span />
                    </div>
                    <div class="signal-destinations">
                        <article>
                            <span class="signal-icon acid"
                                ><AppIcon name="discord" :size="21"
                            /></span>
                            <div>
                                <small>DISCORD</small><strong>#orders</strong>
                            </div>
                            <span class="signal-check">✓</span>
                        </article>
                        <article>
                            <span class="signal-icon sky"
                                ><AppIcon name="webhook" :size="21"
                            /></span>
                            <div>
                                <small>HTTP</small><strong>ERP API</strong>
                            </div>
                            <span class="signal-check">✓</span>
                        </article>
                        <article>
                            <span class="signal-icon amber"
                                ><AppIcon name="digest" :size="21"
                            /></span>
                            <div>
                                <small>EMAIL DIGEST</small
                                ><strong>18:00</strong>
                            </div>
                            <span class="signal-queued">QUEUED</span>
                        </article>
                    </div>
                    <p class="signal-caption">
                        One request in. Three independent deliveries out.
                    </p>
                </div>
            </section>

            <section id="model" class="landing-section model-section">
                <header class="landing-section-head">
                    <span class="eyebrow landing-eyebrow">The model</span>
                    <h2>
                        Small enough to understand.<br />Useful enough to keep.
                    </h2>
                    <p>
                        Hookroute sits between producers and destinations. It
                        receives an event once, then applies explicit routes—no
                        canvas, no mystery state, no unrelated automation
                        machinery.
                    </p>
                </header>

                <div class="model-grid">
                    <article>
                        <span class="model-number">01</span>
                        <AppIcon name="source" :size="28" />
                        <h3>Capture</h3>
                        <p>
                            Give every source a private endpoint. Keep the raw
                            payload, verify optional HMAC signatures and make
                            every request inspectable.
                        </p>
                    </article>
                    <article>
                        <span class="model-number">02</span>
                        <AppIcon name="route" :size="28" />
                        <h3>Route</h3>
                        <p>
                            Match payload fields, fan out to several targets and
                            preserve the body or render a destination-specific
                            template.
                        </p>
                    </article>
                    <article>
                        <span class="model-number">03</span>
                        <AppIcon name="destination" :size="28" />
                        <h3>Deliver</h3>
                        <p>
                            Send to HTTP, Discord or email. Retry failures
                            independently, replay them manually, or collect
                            events into a scheduled digest.
                        </p>
                    </article>
                </div>
            </section>

            <section id="features" class="landing-section feature-section">
                <div class="feature-intro">
                    <span class="eyebrow">Built for the useful middle</span>
                    <h2>
                        More reliable than glue code.<br />Less than a workflow
                        platform.
                    </h2>
                    <p>
                        The deliberate constraint is the product: event in,
                        explicit routes, observable delivery out.
                    </p>
                </div>
                <div class="feature-ledger">
                    <article>
                        <span>01</span>
                        <h3>Fan-out without coupling</h3>
                        <p>
                            One producer never needs to know how many consumers
                            exist.
                        </p>
                    </article>
                    <article>
                        <span>02</span>
                        <h3>Payload control</h3>
                        <p>
                            Filter, pass through or transform each route
                            independently.
                        </p>
                    </article>
                    <article>
                        <span>03</span>
                        <h3>Delivery evidence</h3>
                        <p>
                            See attempts, status codes, response excerpts and
                            retries.
                        </p>
                    </article>
                    <article>
                        <span>04</span>
                        <h3>Human-friendly output</h3>
                        <p>
                            Format Discord messages and turn event streams into
                            email digests.
                        </p>
                    </article>
                    <article>
                        <span>05</span>
                        <h3>Shared projects</h3>
                        <p>
                            Invite users and manage sources, routes and
                            destinations together.
                        </p>
                    </article>
                    <article>
                        <span>06</span>
                        <h3>Ordinary infrastructure</h3>
                        <p>
                            Runs with PHP, a database and one cron entry—even on
                            shared hosting.
                        </p>
                    </article>
                </div>
            </section>

            <section id="open-source" class="landing-section source-section">
                <div class="source-copy">
                    <span class="eyebrow landing-eyebrow"
                        >Open source / MIT</span
                    >
                    <h2>Your events.<br />Your server.<br />Your rules.</h2>
                    <p>
                        Hookroute is built to be read, changed and self-hosted.
                        No proprietary runtime and no per-step billing: deploy
                        the Laravel application wherever PHP and MySQL are at
                        home.
                    </p>
                    <a
                        v-if="repositoryUrl"
                        :href="repositoryUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="btn btn-primary"
                        >View the repository on GitHub ↗</a
                    >
                </div>
                <div class="terminal-card" aria-label="Installation example">
                    <div class="terminal-head">
                        <span /><span /><span />
                        <strong>DEPLOYMENT</strong>
                    </div>
                    <pre><code><span>$</span> git clone {{ repositoryUrl ?? 'hookroute' }}
<span>$</span> composer install
<span>$</span> sail artisan migrate --seed
<span>$</span> sail npm run build

<i>✓ routes cached</i>
<i>✓ scheduler ready</i>
<i>✓ no resident worker required</i></code></pre>
                </div>
            </section>

            <section id="pricing" class="landing-section pricing-section">
                <header class="landing-section-head">
                    <span class="eyebrow landing-eyebrow">Clear pricing</span>
                    <h2>No call required to learn the price.</h2>
                    <p>
                        Use it for free, or pay once if you would rather have
                        the installation handled for you.
                    </p>
                </header>

                <div class="pricing-grid">
                    <article class="price-card">
                        <span class="price-kicker">SELF-HOSTED</span>
                        <h3>Community</h3>
                        <p class="price">
                            <strong>0 €</strong><span>forever</span>
                        </p>
                        <p class="price-summary">
                            The complete application under the MIT license.
                        </p>
                        <ul>
                            <li>
                                <AppIcon name="check" :size="16" />All product
                                features
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />Unlimited
                                local projects and users
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />Run it on
                                your own infrastructure
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />Community
                                support via GitHub
                            </li>
                        </ul>
                        <a
                            v-if="repositoryUrl"
                            :href="repositoryUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="btn btn-dark"
                            >Get the source ↗</a
                        >
                    </article>

                    <article class="price-card featured">
                        <span class="price-kicker">FIXED-PRICE SERVICE</span>
                        <h3>Managed setup</h3>
                        <p class="price">
                            <strong>149 €</strong><span>once, incl. VAT</span>
                        </p>
                        <p class="price-summary">
                            Hookroute installed and configured on your existing
                            PHP/MySQL hosting.
                        </p>
                        <ul>
                            <li>
                                <AppIcon name="check" :size="16" />Deployment
                                and environment setup
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />Database,
                                scheduler and email configuration
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />Initial
                                production check
                            </li>
                            <li>
                                <AppIcon name="check" :size="16" />30 days of
                                setup-related email support
                            </li>
                        </ul>
                        <a
                            :href="`mailto:${contactEmail}?subject=${setupSubject}`"
                            class="btn btn-primary"
                            >Request setup for 149 € →</a
                        >
                        <small>
                            Hosting, domain, ongoing maintenance and custom
                            development are not included. No automatic follow-up
                            charges.
                        </small>
                    </article>
                </div>
            </section>

            <section class="landing-section faq-section">
                <header>
                    <span class="eyebrow landing-eyebrow">Useful answers</span>
                    <h2>Before you route.</h2>
                </header>
                <div class="faq-list">
                    <details open>
                        <summary>Is this a smaller n8n?</summary>
                        <p>
                            In capability, partly. In intent, no. n8n automates
                            general workflows; Hookroute stays focused on
                            receiving, routing, transforming and delivering
                            events. That narrower model is easier to operate and
                            reason about.
                        </p>
                    </details>
                    <details>
                        <summary>Why PHP?</summary>
                        <p>
                            Because it makes the gateway deployable on ordinary
                            webhosting. The database queue and scheduler avoid a
                            mandatory Redis service or permanently running
                            worker.
                        </p>
                    </details>
                    <details>
                        <summary>Are webhooks only alerts?</summary>
                        <p>
                            No. Orders, form submissions, deployment events,
                            payments, device signals and application events all
                            fit the same receive-and-route model.
                        </p>
                    </details>
                </div>
            </section>

            <section class="contact-band">
                <span class="eyebrow">Questions, not sales theatre</span>
                <h2>Tell me what you want to route.</h2>
                <p>
                    No contact form and no hidden quote. Email Torben directly
                    at
                    <span>mail [at] tbuck.de</span>.
                </p>
                <a :href="`mailto:${contactEmail}`" class="btn btn-primary">
                    Write an email →
                </a>
            </section>
        </main>
    </PublicLayout>
</template>
