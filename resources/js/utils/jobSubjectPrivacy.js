/**
 * Client-identifying details must stay out of public job titles.
 * Soft heuristics — prefer false negatives over blocking normal trade language.
 */

export const SUBJECT_PRIVACY_MESSAGE =
    'Don’t put client names, phone numbers, or home addresses in the title — that shows on your public page. Use the private Client name field instead.';

const HONORIFIC =
    String.raw`(?:mr|mrs|miss|ms|dr|prof|engr|eng|barr|chief|alhaji|alhaja|pastor|rev|sir|madam|mallam|hajiya)`;

const SKIP_NAME_TOKENS = new Set([
    'mr',
    'mrs',
    'miss',
    'ms',
    'dr',
    'prof',
    'engr',
    'eng',
    'barr',
    'chief',
    'alhaji',
    'alhaja',
    'pastor',
    'rev',
    'sir',
    'madam',
    'mallam',
    'hajiya',
    'the',
    'and',
    'of',
    'for',
]);

/**
 * @param {string} subject
 * @param {{ clientName?: string, clientWhatsapp?: string }} [context]
 * @returns {string} Error message, or empty string when OK.
 */
export function subjectPrivacyViolation(subject, context = {}) {
    const text = String(subject || '').trim();
    if (!text) {
        return '';
    }

    if (/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i.test(text)) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    const digits = text.replace(/\D+/g, '');
    if (/(?:\+?234|0)[789][01]\d{8}/.test(digits) || /(?<!\d)(?:\+?\d[\d\s().-]{8,}\d)(?!\d)/.test(text)) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    const wa = String(context.clientWhatsapp || '').replace(/\D+/g, '');
    if (wa.length >= 10 && digits.includes(wa.slice(-10))) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    const honorificRe = new RegExp(
        String.raw`\b${HONORIFIC}\.?\s+[A-ZÀ-ÖØ-Þ][\p{L}'-]{1,}`,
        'iu',
    );
    if (honorificRe.test(text)) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    if (/\b(?:no\.?|plot|house|flat|apt\.?|apartment)\s*\d{1,5}\b/i.test(text)) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    if (
        /\b\d{1,4}\s+(?:[\p{L}][\p{L}'-]{1,}[\s-]+){0,3}[\p{L}][\p{L}'-]{1,}\s+(?:street|st\.?|road|rd\.?|avenue|ave\.?|close|crescent|way|drive|dr\.?|lane|boulevard|blvd\.?)\b/iu.test(
            text,
        )
    ) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    if (containsClientNameTokens(text, context.clientName)) {
        return SUBJECT_PRIVACY_MESSAGE;
    }

    return '';
}

function containsClientNameTokens(subject, clientName) {
    const name = String(clientName || '').trim();
    if (!name) {
        return false;
    }

    const subjectNorm = subject.toLowerCase();
    const tokens = name.toLowerCase().split(/[\s,./\\-]+/);

    for (const raw of tokens) {
        const token = raw.replace(/^[.'"]+|[.'"]+$/g, '');
        if (!token || token.length < 3 || SKIP_NAME_TOKENS.has(token)) {
            continue;
        }
        const re = new RegExp(String.raw`\b${escapeRegExp(token)}\b`, 'u');
        if (re.test(subjectNorm)) {
            return true;
        }
    }

    return false;
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
