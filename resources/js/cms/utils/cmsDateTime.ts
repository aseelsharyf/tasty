export const CMS_TIME_ZONE = 'Indian/Maldives';
export const CMS_TIME_ZONE_LABEL = 'MVT';

export function formatCmsDateTime(value: string | Date): string {
    const date = value instanceof Date ? value : new Date(value);

    return new Intl.DateTimeFormat('en-GB', {
        timeZone: CMS_TIME_ZONE,
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).format(date);
}

export function toCmsDateTimeInput(value: string | Date): string {
    const date = value instanceof Date ? value : new Date(value);
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: CMS_TIME_ZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);
    const part = (type: Intl.DateTimeFormatPartTypes): string => parts.find((item) => item.type === type)?.value ?? '';

    return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`;
}

export function cmsDateTimeInputToUtc(value: string): string {
    return new Date(`${value}:00+05:00`).toISOString();
}
