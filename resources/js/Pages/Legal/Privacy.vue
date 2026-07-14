<script setup lang="ts">
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{ repositoryUrl?: string }>();

const contactEmail = ['mail', 'tbuck.de'].join('@');
</script>

<template>
    <Head title="Datenschutz" />
    <PublicLayout :repository-url="repositoryUrl">
        <main class="legal-page">
            <header class="legal-hero">
                <span class="eyebrow landing-eyebrow">Legal</span>
                <h1>Datenschutz</h1>
                <p>Informationen zur Verarbeitung personenbezogener Daten</p>
            </header>

            <article class="legal-content">
                <section>
                    <h2>1. Verantwortlicher</h2>
                    <p>
                        <strong>Torben Buck (tbuck software)</strong><br />
                        Zu den Weiden 12<br />
                        58339 Breckerfeld<br />
                        Deutschland<br />
                        E-Mail:
                        <a :href="`mailto:${contactEmail}`">{{
                            contactEmail
                        }}</a>
                    </p>
                </section>

                <section>
                    <h2>2. Bereitstellung und technische Protokolle</h2>
                    <p>
                        Beim Aufruf der Website werden technisch erforderliche
                        Daten wie IP-Adresse, Zeitpunkt, angeforderte Ressource,
                        Referrer und Browserkennung verarbeitet. Dies dient der
                        sicheren und störungsfreien Bereitstellung des Dienstes
                        auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Hosting-
                        und E-Mail-Dienstleister können dabei als
                        Auftragsverarbeiter eingesetzt werden.
                    </p>
                </section>

                <section>
                    <h2>3. Konten und Projekte</h2>
                    <p>
                        Bei Registrierung und Nutzung verarbeitet Hookroute
                        Namen, E-Mail-Adressen, Passwort-Hashes,
                        Projektzuordnungen, Rollen und Einladungen. Die
                        Verarbeitung ist zur Bereitstellung des Dienstes und zur
                        Durchführung vorvertraglicher oder vertraglicher
                        Maßnahmen erforderlich (Art. 6 Abs. 1 lit. b DSGVO).
                    </p>
                </section>

                <section>
                    <h2>4. Webhook- und Zustelldaten</h2>
                    <p>
                        Hookroute verarbeitet die von Nutzern an Quellen
                        gesendeten Payloads, ausgewählte HTTP-Header,
                        Zielkonfigurationen, Zustellversuche, Statuscodes und
                        begrenzte Antwortauszüge. Geheimnisse werden
                        verschlüsselt gespeichert; sensible eingehende Header
                        werden nicht dauerhaft abgelegt. Nutzer sind dafür
                        verantwortlich, nur rechtmäßig erhobene Daten an den
                        Dienst zu übermitteln.
                    </p>
                    <p>
                        Ereignisse und zugehörige Zustelldaten werden nach der
                        im Projekt festgelegten Aufbewahrungszeit gelöscht. Bei
                        einer vom Betreiber gehosteten Instanz werden die
                        Einzelheiten der Auftragsverarbeitung bei Bedarf in
                        einem Vertrag nach Art. 28 DSGVO geregelt.
                    </p>
                </section>

                <section>
                    <h2>5. E-Mail-Ziele und Kontaktaufnahme</h2>
                    <p>
                        Wenn E-Mail-Ziele oder Zusammenfassungen konfiguriert
                        werden, übermittelt Hookroute die dafür notwendigen
                        Inhalte an den konfigurierten E-Mail-Dienstleister und
                        an die angegebenen Empfänger. Bei einer direkten
                        Kontaktaufnahme per E-Mail werden Absenderadresse,
                        Nachricht und weitere freiwillig übermittelte Angaben
                        zur Bearbeitung der Anfrage verarbeitet.
                    </p>
                </section>

                <section>
                    <h2>6. Cookies</h2>
                    <p>
                        Die Anwendung setzt ausschließlich technisch notwendige
                        Cookies für Sitzung, Anmeldung und Schutz vor
                        Cross-Site-Request-Forgery. Es werden keine Werbe-,
                        Analyse- oder Tracking-Cookies eingesetzt.
                    </p>
                </section>

                <section>
                    <h2>7. Externe Links</h2>
                    <p>
                        Die Website bindet keine externen Analysewerkzeuge oder
                        Schriftarten ein. Beim Öffnen externer Links—etwa zu
                        GitHub—gelten die Datenschutzbestimmungen des jeweiligen
                        Anbieters. Daten werden erst durch das aktive Öffnen des
                        Links an diesen Anbieter übermittelt.
                    </p>
                </section>

                <section>
                    <h2>8. Speicherdauer und Rechte</h2>
                    <p>
                        Kontodaten werden grundsätzlich bis zur Löschung des
                        Kontos gespeichert, soweit keine gesetzlichen
                        Aufbewahrungspflichten entgegenstehen. Betroffene haben
                        nach Maßgabe der DSGVO insbesondere Rechte auf Auskunft,
                        Berichtigung, Löschung, Einschränkung,
                        Datenübertragbarkeit und Widerspruch. Außerdem besteht
                        ein Beschwerderecht bei einer
                        Datenschutzaufsichtsbehörde.
                    </p>
                </section>

                <section>
                    <h2>9. Stand</h2>
                    <p>14. Juli 2026</p>
                </section>
            </article>
        </main>
    </PublicLayout>
</template>
