const options: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    timeZone: 'UTC',
};
export const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-ca', options);
};
