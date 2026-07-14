<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ repositoryUrl?: string }>();

const contactEmail = ['mail', 'tbuck.de'].join('@');
</script>

<template>
    <Head title="Impressum" />
    <PublicLayout :repository-url="repositoryUrl">
        <main class="legal-page">
            <header class="legal-hero">
                <span class="eyebrow landing-eyebrow">Legal</span>
                <h1>Impressum</h1>
                <p>Angaben gemäß § 5 Digitale-Dienste-Gesetz (DDG)</p>
            </header>

            <article class="legal-content">
                <section>
                    <h2>Anbieter</h2>
                    <p>
                        <strong>tbuck software</strong><br />
                        Torben Buck<br />
                        Zu den Weiden 12<br />
                        58339 Breckerfeld<br />
                        Deutschland
                    </p>
                    <p>
                        Einzelunternehmen, nicht im Handelsregister eingetragen.
                    </p>
                </section>

                <section>
                    <h2>Kontakt</h2>
                    <p>
                        E-Mail:
                        <a :href="`mailto:${contactEmail}`">{{
                            contactEmail
                        }}</a>
                    </p>
                </section>

                <section>
                    <h2>Umsatzsteuer</h2>
                    <p>
                        Umsatzsteuer-Identifikationsnummer gemäß § 27a
                        Umsatzsteuergesetz: <strong>DE293879233</strong>
                    </p>
                </section>

                <section>
                    <h2>
                        Verantwortlich für journalistisch-redaktionelle Inhalte
                    </h2>
                    <p>
                        Torben Buck, Anschrift wie oben, gemäß § 18 Abs. 2
                        Medienstaatsvertrag (MStV).
                    </p>
                </section>

                <section>
                    <h2>Verbraucherstreitbeilegung</h2>
                    <p>
                        Ich bin nicht bereit und nicht verpflichtet, an
                        Streitbeilegungsverfahren vor einer
                        Verbraucherschlichtungsstelle teilzunehmen.
                    </p>
                </section>
            </article>
        </main>
    </PublicLayout>
</template>
