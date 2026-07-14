import type { SelectOption } from '@/Components/App/AppSelect.vue';

const fallbackTimezones = [
    'Europe/Berlin',
    'Europe/Amsterdam',
    'Europe/Brussels',
    'Europe/Copenhagen',
    'Europe/Helsinki',
    'Europe/Lisbon',
    'Europe/London',
    'Europe/Madrid',
    'Europe/Oslo',
    'Europe/Paris',
    'Europe/Prague',
    'Europe/Rome',
    'Europe/Stockholm',
    'Europe/Vienna',
    'Europe/Warsaw',
    'Europe/Zurich',
    'America/Chicago',
    'America/Los_Angeles',
    'America/New_York',
    'Asia/Singapore',
    'Asia/Tokyo',
    'Australia/Sydney',
    'UTC',
];

export function browserTimezone(): string {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Europe/Berlin';
}

export function timezoneOptions(): SelectOption[] {
    const supported = (
        Intl as typeof Intl & {
            supportedValuesOf?: (key: string) => string[];
        }
    ).supportedValuesOf?.('timeZone');
    const zones = supported?.length ? supported : fallbackTimezones;

    return [...new Set([browserTimezone(), 'Europe/Berlin', ...zones])].map(
        (zone) => ({
            value: zone,
            label: zone.replaceAll('_', ' ').replace('/', ' · '),
            description:
                zone === browserTimezone() ? 'Detected on this device' : zone,
            icon: 'clock',
        }),
    );
}
