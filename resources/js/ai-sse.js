function parseEvent(block) {
    let event = 'message';
    const data = [];
    for (const line of block.split(/\r\n|\r|\n/)) {
        if (!line || line.startsWith(':')) continue;
        const separator = line.indexOf(':');
        const field = separator === -1 ? line : line.slice(0, separator);
        const value = separator === -1 ? '' : line.slice(separator + 1).replace(/^ /, '');
        if (field === 'event') event = value || 'message';
        if (field === 'data') data.push(value);
    }
    if (!data.length) return null;
    return { event, data: data.join('\n') };
}

/** Incrementally decodes SSE frames, including CRLF and transport-split frames. */
export class AiSseParser {
    buffer = '';

    push(chunk) {
        this.buffer += chunk;
        const events = [];
        const delimiter = /\r\n\r\n|\n\n|\r\r|\r\n\n|\n\r\n/g;
        let match;
        let consumed = 0;
        while ((match = delimiter.exec(this.buffer)) !== null) {
            const parsed = parseEvent(this.buffer.slice(consumed, match.index));
            if (parsed) events.push(parsed);
            consumed = delimiter.lastIndex;
        }
        this.buffer = this.buffer.slice(consumed);
        return events;
    }

    finish() {
        const parsed = parseEvent(this.buffer);
        this.buffer = '';
        return parsed ? [parsed] : [];
    }
}
