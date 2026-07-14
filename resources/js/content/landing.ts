export type LandingLocale = 'de' | 'en';

export const landingCopy = {
    de: {
        meta: {
            title: 'Webhook-Routing ohne unnötige Komplexität',
            description:
                'Webhooks mit einem fokussierten Open-Source-Gateway auf Laravel-Basis empfangen, prüfen, umformen und an mehrere Ziele verteilen.',
        },
        hero: {
            eyebrow: 'Ein fokussiertes Webhook-Gateway',
            title: 'Einmal empfangen.',
            emphasis: 'Klar verteilen.',
            lede: 'Webhooks zentral empfangen, prüfen, umformen und an alle benötigten Systeme zustellen. Mit zuverlässigem Fan-out, Wiederholungen und E-Mail-Sammlungen – ohne eine ganze Workflow-Fabrik zu betreiben.',
            primaryCta: 'Erste Quelle anlegen →',
            secondaryCta: 'So funktioniert es ↓',
            technologyLabel: 'Technologie',
        },
        signal: {
            label: 'Beispiel für Webhook-Routing',
            live: 'LIVE-EVENT / EVT_9F31',
            incoming: 'EINGANG',
            verified: 'GEPRÜFT',
            transform: 'filtern + umformen',
            digest: 'E-MAIL-SAMMLUNG',
            queued: 'WARTEND',
            caption:
                'Ein Request hinein. Drei unabhängige Zustellungen hinaus.',
        },
        model: {
            eyebrow: 'Das Modell',
            heading: 'Klein genug, um es zu verstehen.',
            headingSecond: 'Nützlich genug, um zu bleiben.',
            intro: 'Hookroute sitzt zwischen Absendern und Zielen. Ein Event wird einmal empfangen und anschließend über eindeutige Routen verteilt – ohne Canvas, versteckte Zustände oder sachfremde Automatisierungslogik.',
            items: [
                {
                    icon: 'source',
                    title: 'Empfangen',
                    text: 'Jede Quelle erhält einen privaten Endpunkt. Rohdaten bleiben nachvollziehbar, optionale HMAC-Signaturen werden geprüft und jeder Request wird sichtbar.',
                },
                {
                    icon: 'route',
                    title: 'Verteilen',
                    text: 'Payload-Felder filtern, Events an mehrere Ziele senden und pro Route entweder den Originalinhalt erhalten oder eine eigene Vorlage rendern.',
                },
                {
                    icon: 'destination',
                    title: 'Zustellen',
                    text: 'An HTTP, Discord oder E-Mail senden. Fehlversuche unabhängig wiederholen, manuell neu anstoßen oder zu einer geplanten E-Mail-Sammlung bündeln.',
                },
            ],
        },
        features: {
            eyebrow: 'Für genau den nützlichen Mittelweg',
            heading: 'Zuverlässiger als Glue Code.',
            headingSecond: 'Schlanker als eine Workflow-Plattform.',
            intro: 'Die bewusste Beschränkung ist das Produkt: Event hinein, eindeutige Routen, nachvollziehbare Zustellung hinaus.',
            items: [
                {
                    title: 'Entkoppeltes Fan-out',
                    text: 'Der Absender muss nie wissen, wie viele Empfänger ein Event verarbeiten.',
                },
                {
                    title: 'Kontrolle über Payloads',
                    text: 'Jede Route kann unabhängig filtern, durchreichen oder umformen.',
                },
                {
                    title: 'Zustellungen nachvollziehen',
                    text: 'Versuche, Statuscodes, Antwortauszüge und Wiederholungen bleiben sichtbar.',
                },
                {
                    title: 'Lesbare Ausgaben',
                    text: 'Discord-Nachrichten formatieren und Event-Ströme als E-Mail-Sammlung versenden.',
                },
                {
                    title: 'Gemeinsame Projekte',
                    text: 'Benutzer einladen und Quellen, Routen sowie Ziele gemeinsam verwalten.',
                },
                {
                    title: 'Gewöhnliche Infrastruktur',
                    text: 'Läuft mit PHP, einer Datenbank und einem Cronjob – sogar auf Shared Hosting.',
                },
            ],
        },
        source: {
            eyebrow: 'Open Source / MIT',
            heading: 'Deine Events.',
            headingSecond: 'Dein Server.',
            headingThird: 'Deine Regeln.',
            intro: 'Hookroute ist dafür gebaut, gelesen, verändert und selbst gehostet zu werden. Keine proprietäre Laufzeit und keine Abrechnung pro Schritt: Die Laravel-Anwendung läuft überall dort, wo PHP und MySQL zu Hause sind.',
            repositoryCta: 'Repository auf GitHub öffnen ↗',
            terminalLabel: 'Installationsbeispiel',
            ready: [
                'Routen gecacht',
                'Scheduler bereit',
                'kein dauerhafter Worker nötig',
            ],
        },
        pricing: {
            eyebrow: 'Klare Preise',
            heading: 'Kein Gespräch nötig, um den Preis zu erfahren.',
            intro: 'Kostenlos selbst betreiben oder einmalig bezahlen, wenn die Installation für dich erledigt werden soll.',
            community: {
                kicker: 'SELBST GEHOSTET',
                title: 'Community',
                suffix: 'dauerhaft',
                summary: 'Die vollständige Anwendung unter der MIT-Lizenz.',
                items: [
                    'Alle Produktfunktionen',
                    'Unbegrenzte lokale Projekte und Benutzer',
                    'Betrieb auf eigener Infrastruktur',
                    'Community-Support über GitHub',
                ],
                cta: 'Quellcode öffnen ↗',
            },
            setup: {
                kicker: 'EINRICHTUNG ZUM FESTPREIS',
                title: 'Managed Setup',
                suffix: 'einmalig, inkl. USt.',
                summary:
                    'Hookroute wird auf deinem bestehenden PHP/MySQL-Webhosting installiert und eingerichtet.',
                items: [
                    'Deployment und Umgebungskonfiguration',
                    'Datenbank, Scheduler und E-Mail-Versand',
                    'Erster Produktionscheck',
                    '30 Tage E-Mail-Support zur Einrichtung',
                ],
                cta: 'Einrichtung für 149 € anfragen →',
                note: 'Hosting, Domain, laufende Wartung und individuelle Entwicklung sind nicht enthalten. Es entstehen keine automatischen Folgekosten.',
                subject: 'Hookroute-Einrichtung für 149 EUR',
            },
        },
        faq: {
            eyebrow: 'Hilfreiche Antworten',
            heading: 'Bevor du verteilst.',
            items: [
                {
                    question: 'Ist das ein kleineres n8n?',
                    answer: 'Vom Funktionsumfang teilweise, von der Absicht her nein. n8n automatisiert allgemeine Workflows; Hookroute konzentriert sich auf das Empfangen, Verteilen, Umformen und Zustellen von Events. Dieses engere Modell lässt sich leichter betreiben und verstehen.',
                },
                {
                    question: 'Warum PHP?',
                    answer: 'Weil das Gateway dadurch auf gewöhnlichem Webhosting laufen kann. Datenbankwarteschlange und Scheduler vermeiden einen verpflichtenden Redis-Dienst oder dauerhaft laufenden Worker.',
                },
                {
                    question: 'Sind Webhooks nur für Alerts?',
                    answer: 'Nein. Bestellungen, Formulareingänge, Deployments, Zahlungen, Gerätesignale und Anwendungsereignisse passen alle in dasselbe Modell aus Empfangen und Verteilen.',
                },
            ],
        },
        contact: {
            eyebrow: 'Fragen ohne Verkaufstheater',
            heading: 'Erzähl mir, was du verteilen möchtest.',
            intro: 'Kein Kontaktformular und kein verstecktes Angebot. Schreib Torben direkt an',
            cta: 'E-Mail schreiben →',
        },
    },
    en: {
        meta: {
            title: 'Webhook routing without the machinery',
            description:
                'Receive, inspect, transform and fan out webhooks with a focused open-source Laravel gateway.',
        },
        hero: {
            eyebrow: 'A focused webhook gateway',
            title: 'Receive once.',
            emphasis: 'Route clearly.',
            lede: 'Capture, inspect, reshape and deliver events to every system that needs them. Reliable fan-out, replay and email digests—without running a workflow factory.',
            primaryCta: 'Create your first source →',
            secondaryCta: 'See how it works ↓',
            technologyLabel: 'Technology',
        },
        signal: {
            label: 'Webhook routing example',
            live: 'LIVE EVENT / EVT_9F31',
            incoming: 'INCOMING',
            verified: 'VERIFIED',
            transform: 'filter + transform',
            digest: 'EMAIL DIGEST',
            queued: 'QUEUED',
            caption: 'One request in. Three independent deliveries out.',
        },
        model: {
            eyebrow: 'The model',
            heading: 'Small enough to understand.',
            headingSecond: 'Useful enough to keep.',
            intro: 'Hookroute sits between producers and destinations. It receives an event once, then applies explicit routes—no canvas, no mystery state, no unrelated automation machinery.',
            items: [
                {
                    icon: 'source',
                    title: 'Capture',
                    text: 'Give every source a private endpoint. Keep the raw payload, verify optional HMAC signatures and make every request inspectable.',
                },
                {
                    icon: 'route',
                    title: 'Route',
                    text: 'Match payload fields, fan out to several targets and preserve the body or render a destination-specific template.',
                },
                {
                    icon: 'destination',
                    title: 'Deliver',
                    text: 'Send to HTTP, Discord or email. Retry failures independently, replay them manually, or collect events into a scheduled digest.',
                },
            ],
        },
        features: {
            eyebrow: 'Built for the useful middle',
            heading: 'More reliable than glue code.',
            headingSecond: 'Less than a workflow platform.',
            intro: 'The deliberate constraint is the product: event in, explicit routes, observable delivery out.',
            items: [
                {
                    title: 'Fan-out without coupling',
                    text: 'One producer never needs to know how many consumers exist.',
                },
                {
                    title: 'Payload control',
                    text: 'Filter, pass through or transform each route independently.',
                },
                {
                    title: 'Delivery evidence',
                    text: 'See attempts, status codes, response excerpts and retries.',
                },
                {
                    title: 'Human-friendly output',
                    text: 'Format Discord messages and turn event streams into email digests.',
                },
                {
                    title: 'Shared projects',
                    text: 'Invite users and manage sources, routes and destinations together.',
                },
                {
                    title: 'Ordinary infrastructure',
                    text: 'Runs with PHP, a database and one cron entry—even on shared hosting.',
                },
            ],
        },
        source: {
            eyebrow: 'Open source / MIT',
            heading: 'Your events.',
            headingSecond: 'Your server.',
            headingThird: 'Your rules.',
            intro: 'Hookroute is built to be read, changed and self-hosted. No proprietary runtime and no per-step billing: deploy the Laravel application wherever PHP and MySQL are at home.',
            repositoryCta: 'View the repository on GitHub ↗',
            terminalLabel: 'Installation example',
            ready: [
                'routes cached',
                'scheduler ready',
                'no resident worker required',
            ],
        },
        pricing: {
            eyebrow: 'Clear pricing',
            heading: 'No call required to learn the price.',
            intro: 'Use it for free, or pay once if you would rather have the installation handled for you.',
            community: {
                kicker: 'SELF-HOSTED',
                title: 'Community',
                suffix: 'forever',
                summary: 'The complete application under the MIT license.',
                items: [
                    'All product features',
                    'Unlimited local projects and users',
                    'Run it on your own infrastructure',
                    'Community support via GitHub',
                ],
                cta: 'Get the source ↗',
            },
            setup: {
                kicker: 'FIXED-PRICE SERVICE',
                title: 'Managed setup',
                suffix: 'once, incl. VAT',
                summary:
                    'Hookroute installed and configured on your existing PHP/MySQL hosting.',
                items: [
                    'Deployment and environment setup',
                    'Database, scheduler and email configuration',
                    'Initial production check',
                    '30 days of setup-related email support',
                ],
                cta: 'Request setup for 149 € →',
                note: 'Hosting, domain, ongoing maintenance and custom development are not included. No automatic follow-up charges.',
                subject: 'Hookroute setup for 149 EUR',
            },
        },
        faq: {
            eyebrow: 'Useful answers',
            heading: 'Before you route.',
            items: [
                {
                    question: 'Is this a smaller n8n?',
                    answer: 'In capability, partly. In intent, no. n8n automates general workflows; Hookroute stays focused on receiving, routing, transforming and delivering events. That narrower model is easier to operate and reason about.',
                },
                {
                    question: 'Why PHP?',
                    answer: 'Because it makes the gateway deployable on ordinary webhosting. The database queue and scheduler avoid a mandatory Redis service or permanently running worker.',
                },
                {
                    question: 'Are webhooks only alerts?',
                    answer: 'No. Orders, form submissions, deployment events, payments, device signals and application events all fit the same receive-and-route model.',
                },
            ],
        },
        contact: {
            eyebrow: 'Questions, not sales theatre',
            heading: 'Tell me what you want to route.',
            intro: 'No contact form and no hidden quote. Email Torben directly at',
            cta: 'Write an email →',
        },
    },
} as const;
