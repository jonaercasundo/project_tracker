const escapeHtml = (value) => value.replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
}[character]));

function safeHref(value) {
    const href = value.trim();
    return /^(https?:\/\/|mailto:|#|\/(?!\/))[^\s]*$/i.test(href) ? href : null;
}

function inlineMarkdown(value) {
    const code = [];
    let text = value.replace(/\\([\\`*_{}\[\]()#+.!|>-])/g, '$1');
    text = text.replace(/`([^`]+)`/g, (_, content) => {
        code.push(`<code>${escapeHtml(content)}</code>`);
        return `\u0000${code.length - 1}\u0000`;
    });
    text = escapeHtml(text);
    text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, rawHref) => {
        const href = safeHref(rawHref);
        return href ? `<a href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer">${label}</a>` : label;
    });
    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/__([^_]+)__/g, '<strong>$1</strong>')
        .replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>')
        .replace(/(^|[^_])_([^_]+)_/g, '$1<em>$2</em>');
    return text.replace(/\u0000(\d+)\u0000/g, (_, index) => code[Number(index)]);
}

/** Convert a small, safe Markdown subset into HTML for assistant messages. */
export function renderAiMarkdown(markdown = '') {
    const lines = String(markdown).replace(/\r\n?/g, '\n').split('\n');
    const blocks = [];
    let paragraph = [];
    let list = null;
    let code = null;
    const flushParagraph = () => {
        if (paragraph.length) blocks.push(`<p>${paragraph.map(inlineMarkdown).join('<br>')}</p>`);
        paragraph = [];
    };
    const flushList = () => {
        if (list) blocks.push(`<${list.tag}>${list.items.map((item) => `<li>${inlineMarkdown(item)}</li>`).join('')}</${list.tag}>`);
        list = null;
    };

    for (const line of lines) {
        if (line.trim().startsWith('```')) {
            flushParagraph(); flushList();
            if (code === null) code = [];
            else { blocks.push(`<pre><code>${escapeHtml(code.join('\n'))}</code></pre>`); code = null; }
            continue;
        }
        if (code !== null) { code.push(line); continue; }
        if (!line.trim()) { flushParagraph(); flushList(); continue; }
        const item = line.match(/^\s*(?:[-*+]\s+|(\d+)\.\s+)(.*)$/);
        if (item) {
            flushParagraph();
            const tag = item[1] ? 'ol' : 'ul';
            if (list?.tag !== tag) { flushList(); list = { tag, items: [] }; }
            list.items.push(item[2]);
        } else { flushList(); paragraph.push(line); }
    }
    if (code !== null) blocks.push(`<pre><code>${escapeHtml(code.join('\n'))}</code></pre>`);
    flushParagraph(); flushList();
    return blocks.join('');
}
