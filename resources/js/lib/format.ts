export function formatDate(
    value?: string | null,
    options: Intl.DateTimeFormatOptions = {},
) {
    if (!value) return 'Never';
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
        ...options,
    }).format(new Date(value));
}

export function relativeDate(value?: string | null) {
    if (!value) return 'never';
    const seconds = Math.round((new Date(value).getTime() - Date.now()) / 1000);
    const abs = Math.abs(seconds);
    const [amount, unit] =
        abs < 60
            ? [seconds, 'second']
            : abs < 3600
              ? [Math.round(seconds / 60), 'minute']
              : abs < 86400
                ? [Math.round(seconds / 3600), 'hour']
                : [Math.round(seconds / 86400), 'day'];
    return new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' }).format(
        amount,
        unit as Intl.RelativeTimeFormatUnit,
    );
}
