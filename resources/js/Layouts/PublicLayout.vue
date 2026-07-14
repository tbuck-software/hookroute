<script setup lang="ts">
import type { LandingLocale } from '@/content/landing';
import { Link, usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';

const props = withDefaults(
    defineProps<{
        locale?: LandingLocale;
        repositoryUrl?: string;
    }>(),
    { locale: 'de' },
);

const page = usePage<any>();
const homeUrl = props.locale === 'en' ? route('home.en') : route('home');
const contactEmail = ['mail', 'tbuck.de'].join('@');
const year = new Date().getFullYear();

watchEffect(() => {
    if (typeof document !== 'undefined') {
        document.documentElement.lang = props.locale;
    }
});

const navigation =
    props.locale === 'de'
        ? {
              aria: 'Hauptnavigation',
              tour: 'Einblicke',
              model: 'Funktionsweise',
              features: 'Funktionen',
              pricing: 'Preise',
              source: 'Open Source',
              console: 'Konsole öffnen →',
              signIn: 'Anmelden →',
              description:
                  'Ein fokussiertes, selbst gehostetes Webhook-Gateway.',
              product: 'Produkt',
              project: 'Projekt',
              contact: 'Kontakt',
              imprint: 'Impressum',
              privacy: 'Datenschutz',
          }
        : {
              aria: 'Main navigation',
              tour: 'Product tour',
              model: 'How it works',
              features: 'Features',
              pricing: 'Pricing',
              source: 'Open source',
              console: 'Open console →',
              signIn: 'Sign in →',
              description: 'A focused, self-hosted webhook gateway.',
              product: 'Product',
              project: 'Project',
              contact: 'Contact',
              imprint: 'Imprint',
              privacy: 'Privacy',
          };
</script>

<template>
    <div class="public-site">
        <header class="public-nav">
            <Link :href="homeUrl" class="brand public-brand">
                <span class="brand-mark" />
                <span class="brand-word">hookroute</span>
            </Link>

            <nav class="public-nav-links" :aria-label="navigation.aria">
                <a :href="`${homeUrl}#tour`">{{ navigation.tour }}</a>
                <a :href="`${homeUrl}#model`">{{ navigation.model }}</a>
                <a :href="`${homeUrl}#features`">{{ navigation.features }}</a>
                <a :href="`${homeUrl}#pricing`">{{ navigation.pricing }}</a>
                <a :href="`${homeUrl}#open-source`">{{ navigation.source }}</a>
            </nav>

            <div class="public-nav-actions">
                <div class="language-switch" aria-label="Language / Sprache">
                    <Link
                        :href="route('home')"
                        hreflang="de"
                        :aria-current="locale === 'de' ? 'page' : undefined"
                        :class="{ active: locale === 'de' }"
                        >DE</Link
                    >
                    <Link
                        :href="route('home.en')"
                        hreflang="en"
                        :aria-current="locale === 'en' ? 'page' : undefined"
                        :class="{ active: locale === 'en' }"
                        >EN</Link
                    >
                </div>
                <a
                    v-if="repositoryUrl"
                    :href="repositoryUrl"
                    target="_blank"
                    rel="noreferrer"
                    class="btn public-github-link"
                >
                    GitHub ↗
                </a>
                <Link
                    v-if="page.props.auth.user"
                    :href="route('dashboard')"
                    class="btn btn-primary"
                >
                    {{ navigation.console }}
                </Link>
                <Link v-else :href="route('login')" class="btn btn-primary">
                    {{ navigation.signIn }}
                </Link>
            </div>
        </header>

        <slot />

        <footer class="public-footer">
            <div>
                <Link :href="homeUrl" class="brand public-brand">
                    <span class="brand-mark" />
                    <span class="brand-word">hookroute</span>
                </Link>
                <p>{{ navigation.description }}</p>
            </div>
            <div class="public-footer-links">
                <div>
                    <span class="section-label">{{ navigation.product }}</span>
                    <a :href="`${homeUrl}#tour`">{{ navigation.tour }}</a>
                    <a :href="`${homeUrl}#model`">{{ navigation.model }}</a>
                    <a :href="`${homeUrl}#features`">{{
                        navigation.features
                    }}</a>
                    <a :href="`${homeUrl}#pricing`">{{ navigation.pricing }}</a>
                </div>
                <div>
                    <span class="section-label">{{ navigation.project }}</span>
                    <a
                        v-if="repositoryUrl"
                        :href="repositoryUrl"
                        target="_blank"
                        rel="noreferrer"
                        >GitHub ↗</a
                    >
                    <a :href="`mailto:${contactEmail}`">{{
                        navigation.contact
                    }}</a>
                    <Link :href="route('imprint')">{{
                        navigation.imprint
                    }}</Link>
                    <Link :href="route('privacy')">{{
                        navigation.privacy
                    }}</Link>
                </div>
            </div>
            <p class="public-copyright">© {{ year }} Torben Buck</p>
        </footer>
    </div>
</template>
