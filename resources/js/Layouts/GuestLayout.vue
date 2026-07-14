<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>
<template>
    <div class="guest-shell">
        <aside class="guest-brand">
            <Link href="/" class="brand" style="padding: 0"
                ><span class="brand-mark" />hookroute</Link
            >
            <div class="guest-brand-copy">
                <span class="eyebrow">Webhook operations</span>
                <h1>Every event.<br />Exactly where<br />it belongs.</h1>
                <p>
                    A small, deliberate gateway for capture, routing, delivery
                    and replay.
                </p>
            </div>
            <span
                class="mono"
                style="position: relative; z-index: 1; color: #8f9d94"
                >PHP · Laravel · Vue</span
            >
        </aside>
        <main class="guest-form">
            <div class="guest-form-card"><slot /></div>
        </main>
    </div>
</template>
