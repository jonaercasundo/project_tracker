import test from 'node:test';
import assert from 'node:assert/strict';
import { renderAiMarkdown } from '../resources/js/ai-markdown.js';

test('renders supported markdown and escaped delimiters', () => {
    const html = renderAiMarkdown('**Verified** price\n\n- One\n- `code`\n\n[Open](https://example.com) and \\*italic\\*');
    assert.match(html, /<strong>Verified<\/strong>/);
    assert.match(html, /<ul><li>One<\/li><li><code>code<\/code><\/li><\/ul>/);
    assert.match(html, /rel="noopener noreferrer"/);
    assert.match(html, /<em>italic<\/em>/);
});

test('escapes raw html and rejects unsafe link protocols', () => {
    const html = renderAiMarkdown('<img src=x onerror=alert(1)> [bad](javascript:alert(1))');
    assert.match(html, /&lt;img/);
    assert.doesNotMatch(html, /<img|href="javascript:/i);
});

test('renders paragraphs, line breaks, and fenced code safely', () => {
    const html = renderAiMarkdown('First line\nsecond line\n\n```js\n<script>\n```');
    assert.match(html, /First line<br>second line/);
    assert.match(html, /<pre><code>&lt;script&gt;<\/code><\/pre>/);
});
