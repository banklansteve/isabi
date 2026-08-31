/**
 * Parse a formatted amount string into a number (or empty).
 */
export function parseAmountInput(value) {
    if (value === '' || value === null || value === undefined) {
        return '';
    }

    const cleaned = String(value).replace(/,/g, '').trim();

    if (cleaned === '' || cleaned === '.') {
        return '';
    }

    const num = Number(cleaned);

    return Number.isFinite(num) ? num : '';
}

/**
 * Format a numeric amount with grouping commas.
 */
export function formatAmountInput(value) {
    if (value === '' || value === null || value === undefined) {
        return '';
    }

    const num = Number(value);

    if (!Number.isFinite(num)) {
        return '';
    }

    return num.toLocaleString('en-NG', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
}

/**
 * Insert thousand separators while the user is typing.
 */
export function formatAmountWhileTyping(raw) {
    if (raw === '') {
        return '';
    }

    const [integerPart = '', decimalPart] = raw.split('.');
    const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return decimalPart !== undefined
        ? `${formattedInteger}.${decimalPart}`
        : formattedInteger;
}

export const AMOUNT_INPUT_PATTERN = /^\d*\.?\d{0,2}$/;
