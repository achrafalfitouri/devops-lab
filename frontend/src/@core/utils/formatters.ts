import { isToday } from './helpers'

export const avatarText = (value: string) => {
  if (!value) return '';
  const nameArray = value.split(' ');
  const firstTwoLetters = nameArray
    .slice(0, 2)
    .map(word => word.substring(0, 1).toUpperCase())
    .join('');
  return firstTwoLetters;
};

// TODO: Try to implement this: https://twitter.com/fireship_dev/status/1565424801216311297
export const kFormatter = (num: number) => {
  const regex = /\B(?=(\d{3})+(?!\d))/g

  return Math.abs(num) > 9999 ? `${Math.sign(num) * +((Math.abs(num) / 1000).toFixed(1))}k` : Math.abs(num).toFixed(0).replace(regex, ',')
}

/**
 * Format and return date in Humanize format
 * Intl docs: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/format
 * Intl Constructor: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Intl/DateTimeFormat/DateTimeFormat
 * @param {string} value date to format
 * @param {Intl.DateTimeFormatOptions} formatting Intl object to format with
 */
export const formatDate = (value: string, formatting: Intl.DateTimeFormatOptions = { month: 'short', day: 'numeric', year: 'numeric' }) => {
  if (!value)
    return value

  return new Intl.DateTimeFormat('en-US', formatting).format(new Date(value))
}

export const formatDateToddmmYYYY = (value: string, formatting: Intl.DateTimeFormatOptions = { day: '2-digit', month: '2-digit', year: 'numeric' }) => {
  if (!value)
    return value;

  return new Intl.DateTimeFormat('en-GB', formatting).format(new Date(value)); // 'en-GB' uses dd/mm/yyyy format
}

export function formatDateToYYYYMMDD(date: Date | null | undefined): Date | null {
  if (!date) {
    return null;  // Return null if the date is invalid
  }

  const year = date.getFullYear();
  const month = date.getMonth();  // Keep month zero-based
  const day = date.getDate();

  return new Date(year, month, day);  // Return a Date object
}

export function formatDateToYYYYMMDDV2(date: string | Date | null | undefined): Date | null {
  if (!date) {
    return null; // Return null if the date is invalid
  }

  // Parse the input if it's a string
  const parsedDate = typeof date === "string" ? new Date(date) : date;

  if (isNaN(parsedDate.getTime())) {
    return null; // Return null if the parsed date is invalid
  }

  const year = parsedDate.getFullYear();
  const month = parsedDate.getMonth(); // Months are zero-based
  const day = parsedDate.getDate();

  // Return a Date object with the time set to midnight
  return new Date(year, month, day);
}


export function formatDateDDMMYYYY  (dateString: any) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR');
};

/**
 * Return short human friendly month representation of date
 * Can also convert date to only time if date is of today (Better UX)
 * @param {string} value date to format
 * @param {boolean} toTimeForCurrentDay Shall convert to time if day is today/current
 */
export const formatDateToMonthShort = (value: string, toTimeForCurrentDay = true) => {
  const date = new Date(value)
  let formatting: Record<string, string> = { month: 'short', day: 'numeric' }

  if (toTimeForCurrentDay && isToday(date))
    formatting = { hour: 'numeric', minute: 'numeric' }

  return new Intl.DateTimeFormat('en-US', formatting).format(new Date(value))
}

export const prefixWithPlus = (value: number) => value > 0 ? `+${value}` : value
