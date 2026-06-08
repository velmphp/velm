import Prism from 'prismjs';
import 'prismjs/components/prism-clike';
import 'prismjs/components/prism-javascript';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-markup';
import 'prismjs/components/prism-css';
import 'prismjs/components/prism-python';

const languageAliases = {
    js: 'javascript',
    html: 'markup',
};

function resolveLanguage(raw) {
    if (!raw || raw === 'text') {
        return null;
    }

    return languageAliases[raw] ?? raw;
}

function highlightCodeDisplays(root = document) {
    root.querySelectorAll('[data-pv-code-display] code').forEach((block) => {
        const language = [...block.classList]
            .map((className) => className.match(/^language-(.+)$/)?.[1])
            .find(Boolean);
        const grammar = language ? Prism.languages[language] : null;

        if (!grammar) {
            return;
        }

        const source = block.textContent ?? '';

        block.innerHTML = Prism.highlight(source, grammar, language);
    });
}

window.pvHighlightCodeDisplays = highlightCodeDisplays;

function boot() {
    highlightCodeDisplays();
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);

if (document.readyState !== 'loading') {
    boot();
}
