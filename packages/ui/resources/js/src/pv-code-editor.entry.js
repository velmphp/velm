import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import { css } from '@codemirror/lang-css';
import { html } from '@codemirror/lang-html';
import { javascript } from '@codemirror/lang-javascript';
import { json } from '@codemirror/lang-json';
import { python } from '@codemirror/lang-python';
import { syntaxHighlighting, defaultHighlightStyle } from '@codemirror/language';
import { EditorState } from '@codemirror/state';
import { oneDark } from '@codemirror/theme-one-dark';
import { EditorView, keymap, lineNumbers } from '@codemirror/view';

const languages = {
    json: () => json(),
    javascript: () => javascript(),
    js: () => javascript(),
    html: () => html(),
    css: () => css(),
    python: () => python(),
    text: () => [],
};

function velmTheme() {
    return EditorView.theme({
        '&': {
            fontSize: '0.8125rem',
            backgroundColor: 'var(--color-neutral-primary)',
            color: 'var(--color-body)',
        },
        '.cm-content': {
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace',
            caretColor: 'var(--color-fg-brand)',
        },
        '.cm-gutters': {
            backgroundColor: 'var(--color-neutral-secondary-soft)',
            color: 'var(--color-body-subtle)',
            borderRight: '1px solid var(--color-default)',
        },
        '&.cm-focused': {
            outline: '2px solid color-mix(in srgb, var(--color-fg-brand) 35%, transparent)',
            outlineOffset: '-1px',
        },
    });
}

function stringifyEditorValue(value) {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'string') {
        return value;
    }
    try {
        return JSON.stringify(value, null, 2);
    } catch (_) {
        return String(value);
    }
}

function hasWireValue(value) {
    return value !== undefined && value !== null && String(value) !== '';
}

function resolveInitial(cfg, alpine) {
    if (hasWireValue(cfg.initial)) {
        return cfg.initial;
    }

    if (typeof window.pvWireGet === 'function' && alpine.wireKey) {
        const fromWire = window.pvWireGet(alpine, alpine.wireKey);
        if (hasWireValue(fromWire)) {
            return fromWire;
        }
    }

    return cfg.initial ?? '';
}

/**
 * CodeMirror + Alpine: keep EditorView in a closure — Alpine proxies break CM views.
 */
function pvCodeEditor(cfg) {
    let view = null;

    return {
        wireKey: cfg.wireKey || '',
        readonly: !!cfg.readonly,
        language: (cfg.language || 'json').toLowerCase(),

        init() {
            if (this.readonly) {
                return;
            }

            const host = this.$refs.mount;
            if (!host) {
                return;
            }

            if (view) {
                view.destroy();
                view = null;
            }

            host.replaceChildren();

            const initial = stringifyEditorValue(resolveInitial(cfg, this));
            const wireEl = this.$refs.wireField;
            if (wireEl) {
                wireEl.value = initial;
            }

            const alpine = this;
            const isDark = document.documentElement.classList.contains('dark');
            const langKey = languages[this.language] ? this.language : 'json';
            const lang = languages[langKey]();
            const extensions = [
                lineNumbers(),
                history(),
                keymap.of([...defaultKeymap, ...historyKeymap]),
                lang,
                syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
                velmTheme(),
                EditorView.lineWrapping,
                EditorView.editable.of(true),
                EditorView.updateListener.of((update) => {
                    if (!update.docChanged) {
                        return;
                    }
                    const text = update.state.doc.toString();
                    if (wireEl) {
                        wireEl.value = text;
                    }
                }),
            ];

            if (isDark) {
                extensions.push(oneDark);
            }

            view = new EditorView({
                parent: host,
                state: EditorState.create({
                    doc: initial,
                    extensions,
                }),
            });
        },

        flushToWire() {
            if (!view || !this.wireKey) {
                return;
            }

            const text = view.state.doc.toString();
            const wireEl = this.$refs.wireField;
            if (wireEl) {
                wireEl.value = text;
            }

            if (typeof this.$wire !== 'undefined') {
                if (typeof this.$wire.set === 'function') {
                    this.$wire.set(this.wireKey, text);
                } else if (typeof this.$wire.$set === 'function') {
                    this.$wire.$set(this.wireKey, text);
                }
            }
        },

        destroy() {
            if (view) {
                const text = view.state.doc.toString();
                const wireEl = this.$refs.wireField;
                if (wireEl) {
                    wireEl.value = text;
                }
                view.destroy();
            }
            view = null;
        },
    };
}

window.pvCodeEditor = pvCodeEditor;

function flushCodeEditors() {
    document.querySelectorAll('[data-pv-code-editor]').forEach((root) => {
        const data = typeof Alpine !== 'undefined' && typeof Alpine.$data === 'function'
            ? Alpine.$data(root)
            : null;
        if (data && typeof data.flushToWire === 'function') {
            data.flushToWire();
        }
    });
}

if (!window.__pvCodeEditorFormHook) {
    window.__pvCodeEditorFormHook = true;
    document.addEventListener(
        'submit',
        (event) => {
            if (event.target?.id === 'velm-form') {
                flushCodeEditors();
            }
        },
        true,
    );
}

function register() {
    if (typeof Alpine === 'undefined') {
        return;
    }
    Alpine.data('pvCodeEditor', pvCodeEditor);
}

document.addEventListener('alpine:init', register);
document.addEventListener('livewire:navigated', register);
if (typeof window.pvRegisterEditorWidgets === 'function') {
    window.pvRegisterEditorWidgets();
} else if (typeof Alpine !== 'undefined') {
    register();
}
