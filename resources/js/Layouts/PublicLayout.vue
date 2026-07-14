<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    repositoryUrl?: string;
}>();

const page = usePage<any>();
const homeUrl = route('home');
const contactEmail = ['mail', 'tbuck.de'].join('@');
const year = new Date().getFullYear();
</script>

<template>
    <div class="public-site">
        <header class="public-nav">
            <Link :href="homeUrl" class="brand public-brand">
                <span class="brand-mark" />
                <span class="brand-word">hookroute</span>
            </Link>

            <nav class="public-nav-links" aria-label="Main navigation">
                <a :href="`${homeUrl}#model`">How it works</a>
                <a :href="`${homeUrl}#features`">Features</a>
                <a :href="`${homeUrl}#pricing`">Pricing</a>
                <a :href="`${homeUrl}#open-source`">Open source</a>
            </nav>

            <div class="public-nav-actions">
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
                    Open console →
                </Link>
                <Link v-else :href="route('login')" class="btn btn-primary">
                    Sign in →
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
                <p>A focused, self-hosted webhook gateway.</p>
            </div>
            <div class="public-footer-links">
                <div>
                    <span class="section-label">Product</span>
                    <a :href="`${homeUrl}#model`">How it works</a>
                    <a :href="`${homeUrl}#features`">Features</a>
                    <a :href="`${homeUrl}#pricing`">Pricing</a>
                </div>
                <div>
                    <span class="section-label">Project</span>
                    <a
                        v-if="repositoryUrl"
                        :href="repositoryUrl"
                        target="_blank"
                        rel="noreferrer"
                        >GitHub ↗</a
                    >
                    <a :href="`mailto:${contactEmail}`">Contact</a>
                    <Link :href="route('imprint')">Imprint</Link>
                    <Link :href="route('privacy')">Privacy</Link>
                </div>
            </div>
            <p class="public-copyright">© {{ year }} Torben Buck</p>
        </footer>
    </div>
</template>
