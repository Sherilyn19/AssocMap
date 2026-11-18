// Match the server's whitespace-separated word count for the live form counter.
export function countApplicationWords(value) {
    const text = value.trim();
    return text ? text.split(/\s+/u).length : 0;
}
