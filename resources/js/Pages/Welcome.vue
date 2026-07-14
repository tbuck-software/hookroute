<script setup lang="ts">
import AppIcon from '@/Components/App/AppIcon.vue';
import { landingCopy, type LandingLocale } from '@/content/landing';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    canRegister: boolean;
    locale: LandingLocale;
    repositoryUrl?: string;
}>();

const page = usePage<any>();
const copy = computed(() => landingCopy[props.locale]);
const contactEmail = ['mail', 'tbuck.de'].join('@');
const setupSubject = computed(() =>
    encodeURIComponent(copy.value.pricing.setup.subject),
);
const canonicalUrl = computed(() =>
    props.locale === 'de' ? route('home') : route('home.en'),
);
</script>

<template>
    <Head :title="copy.meta.title">
        <meta
            head-key="description"
            name="description"
            :content="copy.meta.description"
        />
        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />
        <link
            head-key="alternate-de"
            rel="alternate"
            hreflang="de"
            :href="route('home')"
        />
        <link
            head-key="alternate-en"
            rel="alternate"
            hreflang="en"
            :href="route('home.en')"
        />
        <link
            head-key="alternate-default"
            rel="alternate"
            hreflang="x-default"
            :href="route('home')"
        />
    </Head>

    <PublicLayout :repository-url="repositoryUrl" :locale="locale">
        <main>
            <section class="landing-hero">
                <div class="landing-hero-copy">
                    <span class="eyebrow landing-eyebrow">
                        {{ copy.hero.eyebrow }}
                    </span>
                    <h1>
                        {{ copy.hero.title }}<em>{{ copy.hero.emphasis }}</em>
                    </h1>
                    <p class="landing-lede">{{ copy.hero.lede }}</p>
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
                            {{ copy.hero.primaryCta }}
                        </Link>
                        <a href="#model" class="btn btn-soft">
                            {{ copy.hero.secondaryCta }}
                        </a>
                    </div>
                    <div
                        class="landing-tech-line"
                        :aria-label="copy.hero.technologyLabel"
                    >
                        <span>Open Source</span>
                        <span>Laravel + Vue</span>
                        <span>PHP 8.3+</span>
                        <span>MySQL or SQLite</span>
                    </div>
                </div>

                <div class="signal-board" :aria-label="copy.signal.label">
                    <div class="signal-board-head">
                        <span class="live-dot" />
                        <span>{{ copy.signal.live }}</span>
                        <span>12:04:18.241</span>
                    </div>
                    <div class="signal-source">
                        <span class="signal-icon">
                            <AppIcon name="source" :size="22" />
                        </span>
                        <div>
                            <small>{{ copy.signal.incoming }}</small>
                            <strong>POST /hooks/orders</strong>
                            <code>{ "event": "order.paid" }</code>
                        </div>
                        <span class="signal-status">
                            {{ copy.signal.verified }}
                        </span>
                    </div>
                    <div class="signal-path" aria-hidden="true">
                        <span />
                        <strong>{{ copy.signal.transform }}</strong>
                        <span />
                    </div>
                    <div class="signal-destinations">
                        <article>
                            <span class="signal-icon acid">
                                <AppIcon name="discord" :size="21" />
                            </span>
                            <div>
                                <small>DISCORD</small><strong>#orders</strong>
                            </div>
                            <span class="signal-check">✓</span>
                        </article>
                        <article>
                            <span class="signal-icon sky">
                                <AppIcon name="webhook" :size="21" />
                            </span>
                            <div>
                                <small>HTTP</small><strong>ERP API</strong>
                            </div>
                            <span class="signal-check">✓</span>
                        </article>
                        <article>
                            <span class="signal-icon amber">
                                <AppIcon name="digest" :size="21" />
                            </span>
                            <div>
                                <small>{{ copy.signal.digest }}</small>
                                <strong>18:00</strong>
                            </div>
                            <span class="signal-queued">
                                {{ copy.signal.queued }}
                            </span>
                        </article>
                    </div>
                    <p class="signal-caption">{{ copy.signal.caption }}</p>
                </div>
            </section>

            <section id="model" class="landing-section model-section">
                <header class="landing-section-head">
                    <span class="eyebrow landing-eyebrow">
                        {{ copy.model.eyebrow }}
                    </span>
                    <h2>
                        {{ copy.model.heading }}<br />{{
                            copy.model.headingSecond
                        }}
                    </h2>
                    <p>{{ copy.model.intro }}</p>
                </header>

                <div class="model-grid">
                    <article
                        v-for="(item, index) in copy.model.items"
                        :key="item.title"
                    >
                        <span class="model-number">
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                        <AppIcon :name="item.icon" :size="28" />
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.text }}</p>
                    </article>
                </div>
            </section>

            <section id="features" class="landing-section feature-section">
                <div class="feature-intro">
                    <span class="eyebrow">{{ copy.features.eyebrow }}</span>
                    <h2>
                        {{ copy.features.heading }}<br />{{
                            copy.features.headingSecond
                        }}
                    </h2>
                    <p>{{ copy.features.intro }}</p>
                </div>
                <div class="feature-ledger">
                    <article
                        v-for="(item, index) in copy.features.items"
                        :key="item.title"
                    >
                        <span>{{ String(index + 1).padStart(2, '0') }}</span>
                        <h3>{{ item.title }}</h3>
                        <p>{{ item.text }}</p>
                    </article>
                </div>
            </section>

            <section id="open-source" class="landing-section source-section">
                <div class="source-copy">
                    <span class="eyebrow landing-eyebrow">
                        {{ copy.source.eyebrow }}
                    </span>
                    <h2>
                        {{ copy.source.heading }}<br />{{
                            copy.source.headingSecond
                        }}<br />{{ copy.source.headingThird }}
                    </h2>
                    <p>{{ copy.source.intro }}</p>
                    <a
                        v-if="repositoryUrl"
                        :href="repositoryUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="btn btn-primary"
                    >
                        {{ copy.source.repositoryCta }}
                    </a>
                </div>
                <div
                    class="terminal-card"
                    :aria-label="copy.source.terminalLabel"
                >
                    <div class="terminal-head">
                        <span /><span /><span />
                        <strong>DEPLOYMENT</strong>
                    </div>
                    <pre><code><span>$</span> git clone {{ repositoryUrl ?? 'hookroute' }}
<span>$</span> composer install
<span>$</span> sail artisan migrate --seed
<span>$</span> sail npm run build

<i v-for="line in copy.source.ready" :key="line">✓ {{ line }}
</i></code></pre>
                </div>
            </section>

            <section id="pricing" class="landing-section pricing-section">
                <header class="landing-section-head">
                    <span class="eyebrow landing-eyebrow">
                        {{ copy.pricing.eyebrow }}
                    </span>
                    <h2>{{ copy.pricing.heading }}</h2>
                    <p>{{ copy.pricing.intro }}</p>
                </header>

                <div class="pricing-grid">
                    <article class="price-card">
                        <span class="price-kicker">
                            {{ copy.pricing.community.kicker }}
                        </span>
                        <h3>{{ copy.pricing.community.title }}</h3>
                        <p class="price">
                            <strong>0 €</strong>
                            <span>{{ copy.pricing.community.suffix }}</span>
                        </p>
                        <p class="price-summary">
                            {{ copy.pricing.community.summary }}
                        </p>
                        <ul>
                            <li
                                v-for="item in copy.pricing.community.items"
                                :key="item"
                            >
                                <AppIcon name="check" :size="16" />{{ item }}
                            </li>
                        </ul>
                        <a
                            v-if="repositoryUrl"
                            :href="repositoryUrl"
                            target="_blank"
                            rel="noreferrer"
                            class="btn btn-dark"
                        >
                            {{ copy.pricing.community.cta }}
                        </a>
                    </article>

                    <article class="price-card featured">
                        <span class="price-kicker">
                            {{ copy.pricing.setup.kicker }}
                        </span>
                        <h3>{{ copy.pricing.setup.title }}</h3>
                        <p class="price">
                            <strong>149 €</strong>
                            <span>{{ copy.pricing.setup.suffix }}</span>
                        </p>
                        <p class="price-summary">
                            {{ copy.pricing.setup.summary }}
                        </p>
                        <ul>
                            <li
                                v-for="item in copy.pricing.setup.items"
                                :key="item"
                            >
                                <AppIcon name="check" :size="16" />{{ item }}
                            </li>
                        </ul>
                        <a
                            :href="`mailto:${contactEmail}?subject=${setupSubject}`"
                            class="btn btn-primary"
                        >
                            {{ copy.pricing.setup.cta }}
                        </a>
                        <small>{{ copy.pricing.setup.note }}</small>
                    </article>
                </div>
            </section>

            <section class="landing-section faq-section">
                <header>
                    <span class="eyebrow landing-eyebrow">
                        {{ copy.faq.eyebrow }}
                    </span>
                    <h2>{{ copy.faq.heading }}</h2>
                </header>
                <div class="faq-list">
                    <details
                        v-for="(item, index) in copy.faq.items"
                        :key="item.question"
                        :open="index === 0"
                    >
                        <summary>{{ item.question }}</summary>
                        <p>{{ item.answer }}</p>
                    </details>
                </div>
            </section>

            <section class="contact-band">
                <span class="eyebrow">{{ copy.contact.eyebrow }}</span>
                <h2>{{ copy.contact.heading }}</h2>
                <p>
                    {{ copy.contact.intro }}
                    <span>mail [at] tbuck.de</span>.
                </p>
                <a :href="`mailto:${contactEmail}`" class="btn btn-primary">
                    {{ copy.contact.cta }}
                </a>
            </section>
        </main>
    </PublicLayout>
</template>
