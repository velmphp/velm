import { Editor } from '@tiptap/core';
import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import ImageResize from 'tiptap-extension-resize-image';

const HIGHLIGHT_COLOR = '#fef9c3';

/** Persist resize/align attrs in saved HTML (package omits renderHTML on custom attrs). */
const VelmImageResize = ImageResize.extend({
    addAttributes() {
        const attrs = this.parent?.() ?? {};

        if (attrs.containerStyle) {
            attrs.containerStyle = {
                ...attrs.containerStyle,
                renderHTML: (attributes) =>
                    attributes.containerStyle
                        ? { containerstyle: attributes.containerStyle }
                        : {},
            };
        }

        if (attrs.wrapperStyle) {
            attrs.wrapperStyle = {
                ...attrs.wrapperStyle,
                renderHTML: (attributes) =>
                    attributes.wrapperStyle ? { wrapperstyle: attributes.wrapperStyle } : {},
            };
        }

        attrs.width = {
            default: null,
            parseHTML: (element) => element.getAttribute('width'),
            renderHTML: (attributes) => (attributes.width ? { width: attributes.width } : {}),
        };

        return attrs;
    },
});

function hydrateRichTextDisplayImages(root = document) {
    root.querySelectorAll('.pv-rich-text-display--simple img[containerstyle]').forEach((img) => {
        const containerStyle = img.getAttribute('containerstyle');
        const wrapperStyle = img.getAttribute('wrapperstyle');

        if (containerStyle) {
            img.setAttribute('style', containerStyle);
        }

        if (wrapperStyle && !img.parentElement?.dataset?.pvImageResizeWrap) {
            const wrap = document.createElement('div');
            wrap.dataset.pvImageResizeWrap = 'true';
            wrap.setAttribute('style', wrapperStyle);
            img.parentNode?.insertBefore(wrap, img);
            wrap.appendChild(img);
        }
    });
}

function toEditorHtml(raw) {
    let content = String(raw ?? '');
    if (content !== '' && !/<[a-z][\s\S]*>/i.test(content)) {
        const escaped = content
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
        content = `<p>${escaped}</p>`;
    }

    return content;
}

/**
 * TipTap + Alpine: never store Editor on `this` — Alpine proxies break ProseMirror.
 * @see https://tiptap.dev/docs/editor/getting-started/install/alpine
 */
function pvRichText(cfg) {
    let editor = null;

    return {
        wireKey: cfg.wireKey || '',
        readonly: !!cfg.readonly,
        placeholder: cfg.placeholder || '',
        pickerTitle: cfg.pickerTitle || 'Choose image',
        updatedAt: Date.now(),
        headingMenuOpen: false,
        listMenuOpen: false,

        init() {
            if (this.readonly || typeof Editor === 'undefined') {
                return;
            }

            const host = this.$refs.mount;
            if (!host) {
                return;
            }

            if (editor) {
                editor.destroy();
                editor = null;
            }

            const html = toEditorHtml(cfg.initial);
            const wireEl = this.$refs.wireField;
            if (wireEl) {
                wireEl.value = html;
            }

            const alpine = this;
            const editorShell = host.closest('.pv-rich-text-editor--simple') || host.parentElement;
            const editorWidth = editorShell?.clientWidth || host.clientWidth || 640;
            const maxImageWidth = Math.max(120, editorWidth - 32);

            editor = new Editor({
                element: host,
                editable: true,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [1, 2, 3, 4] },
                    }),
                    Underline,
                    Highlight.configure({ multicolor: false }),
                    Subscript,
                    Superscript,
                    TaskList,
                    TaskItem.configure({ nested: true }),
                    TextAlign.configure({
                        types: ['heading', 'paragraph'],
                    }),
                    Link.configure({
                        openOnClick: false,
                    }),
                    VelmImageResize.configure({
                        inline: false,
                        allowBase64: false,
                        minWidth: 48,
                        maxWidth: maxImageWidth,
                    }),
                    Placeholder.configure({ placeholder: this.placeholder }),
                ],
                content: html,
                editorProps: {
                    attributes: {
                        class: 'tiptap ProseMirror simple-editor',
                        autocomplete: 'off',
                        autocorrect: 'off',
                    },
                },
                onUpdate({ editor: ed }) {
                    alpine.updatedAt = Date.now();
                    if (wireEl) {
                        wireEl.value = ed.getHTML();
                    }
                },
                onSelectionUpdate() {
                    alpine.updatedAt = Date.now();
                },
            });
        },

        closeMenus() {
            this.headingMenuOpen = false;
            this.listMenuOpen = false;
        },

        positionMenu(menuRef, triggerRef) {
            const menu = this.$refs[menuRef];
            const trigger = this.$refs[triggerRef];
            if (!menu || !trigger) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            menu.style.position = 'fixed';
            menu.style.top = `${rect.bottom + 4}px`;
            menu.style.left = `${rect.left}px`;
            menu.style.zIndex = '200';
            menu.style.minWidth = `${Math.max(rect.width, 160)}px`;
        },

        toggleHeadingMenu() {
            this.listMenuOpen = false;
            this.headingMenuOpen = !this.headingMenuOpen;
            if (this.headingMenuOpen) {
                this.$nextTick(() => this.positionMenu('headingMenu', 'headingTrigger'));
            }
        },

        toggleListMenu() {
            this.headingMenuOpen = false;
            this.listMenuOpen = !this.listMenuOpen;
            if (this.listMenuOpen) {
                this.$nextTick(() => this.positionMenu('listMenu', 'listTrigger'));
            }
        },

        flushToWire() {
            if (!editor || editor.isDestroyed || !this.wireKey) {
                return;
            }

            const html = editor.getHTML();
            const wireEl = this.$refs.wireField;
            if (wireEl) {
                wireEl.value = html;
            }

            if (typeof this.$wire !== 'undefined') {
                if (typeof this.$wire.set === 'function') {
                    this.$wire.set(this.wireKey, html);
                } else if (typeof this.$wire.$set === 'function') {
                    this.$wire.$set(this.wireKey, html);
                }
            }
        },

        canUndo() {
            void this.updatedAt;

            return editor && !editor.isDestroyed && editor.can().undo();
        },

        canRedo() {
            void this.updatedAt;

            return editor && !editor.isDestroyed && editor.can().redo();
        },

        isActive(cmd) {
            void this.updatedAt;

            if (!editor || editor.isDestroyed) {
                return false;
            }

            const map = {
                bold: () => editor.isActive('bold'),
                italic: () => editor.isActive('italic'),
                strike: () => editor.isActive('strike'),
                code: () => editor.isActive('code'),
                underline: () => editor.isActive('underline'),
                highlight: () => editor.isActive('highlight'),
                link: () => editor.isActive('link'),
                blockquote: () => editor.isActive('blockquote'),
                codeBlock: () => editor.isActive('codeBlock'),
                bullet: () => editor.isActive('bulletList'),
                ordered: () => editor.isActive('orderedList'),
                task: () => editor.isActive('taskList'),
                heading: () => editor.isActive('heading'),
                paragraph: () => editor.isActive('paragraph') && !editor.isActive('heading'),
                list: () =>
                    editor.isActive('bulletList')
                    || editor.isActive('orderedList')
                    || editor.isActive('taskList'),
                superscript: () => editor.isActive('superscript'),
                subscript: () => editor.isActive('subscript'),
                h1: () => editor.isActive('heading', { level: 1 }),
                h2: () => editor.isActive('heading', { level: 2 }),
                h3: () => editor.isActive('heading', { level: 3 }),
                h4: () => editor.isActive('heading', { level: 4 }),
                'align-left': () => editor.isActive({ textAlign: 'left' }),
                'align-center': () => editor.isActive({ textAlign: 'center' }),
                'align-right': () => editor.isActive({ textAlign: 'right' }),
                'align-justify': () => editor.isActive({ textAlign: 'justify' }),
            };

            return map[cmd]?.() ?? false;
        },

        syncWire(wireEl) {
            if (wireEl) {
                wireEl.value = editor.getHTML();
            }
            this.updatedAt = Date.now();
        },

        csrfToken() {
            if (typeof window.pvCsrf === 'function') {
                return window.pvCsrf();
            }

            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },

        async insertImageFromRow(row) {
            if (!row?.id || !editor || editor.isDestroyed) {
                return;
            }

            const downloadUrl = `/api/attachment/${row.id}/download`;

            try {
                await fetch('/web/files/bulk/public', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-Token': this.csrfToken(),
                    },
                    body: JSON.stringify({ ids: [row.id], public: true }),
                });
            } catch (_) {
                /* non-fatal if ACL denies */
            }

            editor.chain().focus().setImage({ src: downloadUrl }).run();
            this.syncWire(this.$refs.wireField);
        },

        pickImage() {
            if (!editor || this.readonly || editor.isDestroyed) {
                return;
            }

            this.closeMenus();

            const params = new URLSearchParams({ accept: 'image/*' });
            const url = `/web/files/picker?${params.toString()}`;

            if (!window.PvDialog) {
                window.location.href = url;

                return;
            }

            window.PvDialog.open({
                url,
                title: this.pickerTitle,
                onResult: (row) => this.insertImageFromRow(row),
            });
        },

        run(cmd) {
            if (!editor || this.readonly || editor.isDestroyed) {
                return;
            }

            const wireEl = this.$refs.wireField;
            this.closeMenus();

            const chain = editor.chain().focus();

            if (cmd === 'undo') {
                editor.commands.undo();
            } else if (cmd === 'redo') {
                editor.commands.redo();
            } else if (cmd === 'link') {
                const prev = editor.getAttributes('link').href || '';
                const url = window.prompt('URL', prev);
                if (url === null) {
                    return;
                }
                if (url === '') {
                    chain.extendMarkRange('link').unsetLink().run();
                } else {
                    chain.extendMarkRange('link').setLink({ href: url }).run();
                }
            } else if (cmd === 'image') {
                this.pickImage();

                return;
            } else if (cmd === 'paragraph') {
                chain.setParagraph().run();
            } else if (cmd === 'h1') {
                chain.toggleHeading({ level: 1 }).run();
            } else if (cmd === 'h2') {
                chain.toggleHeading({ level: 2 }).run();
            } else if (cmd === 'h3') {
                chain.toggleHeading({ level: 3 }).run();
            } else if (cmd === 'h4') {
                chain.toggleHeading({ level: 4 }).run();
            } else if (cmd === 'bold') {
                chain.toggleBold().run();
            } else if (cmd === 'italic') {
                chain.toggleItalic().run();
            } else if (cmd === 'strike') {
                chain.toggleStrike().run();
            } else if (cmd === 'code') {
                chain.toggleCode().run();
            } else if (cmd === 'underline') {
                chain.toggleUnderline().run();
            } else if (cmd === 'highlight') {
                chain.toggleHighlight({ color: HIGHLIGHT_COLOR }).run();
            } else if (cmd === 'superscript') {
                chain.toggleSuperscript().run();
            } else if (cmd === 'subscript') {
                chain.toggleSubscript().run();
            } else if (cmd === 'blockquote') {
                chain.toggleBlockquote().run();
            } else if (cmd === 'codeBlock') {
                chain.toggleCodeBlock().run();
            } else if (cmd === 'bullet') {
                editor.commands.toggleBulletList();
            } else if (cmd === 'ordered') {
                editor.commands.toggleOrderedList();
            } else if (cmd === 'task') {
                editor.commands.toggleTaskList();
            } else if (cmd === 'align-left') {
                chain.setTextAlign('left').run();
            } else if (cmd === 'align-center') {
                chain.setTextAlign('center').run();
            } else if (cmd === 'align-right') {
                chain.setTextAlign('right').run();
            } else if (cmd === 'align-justify') {
                chain.setTextAlign('justify').run();
            }

            this.syncWire(wireEl);
        },

        destroy() {
            if (editor && !editor.isDestroyed) {
                const html = editor.getHTML();
                const wireEl = this.$refs.wireField;
                if (wireEl) {
                    wireEl.value = html;
                }
                editor.destroy();
            }
            editor = null;
        },
    };
}

window.pvRichText = pvRichText;

function flushRichTextEditors() {
    document.querySelectorAll('[data-pv-rich-text]').forEach((root) => {
        const data = typeof Alpine !== 'undefined' && typeof Alpine.$data === 'function'
            ? Alpine.$data(root)
            : null;
        if (data && typeof data.flushToWire === 'function') {
            data.flushToWire();
        }
    });
}

if (!window.__pvRichTextFormHook) {
    window.__pvRichTextFormHook = true;
    document.addEventListener(
        'submit',
        (event) => {
            if (event.target?.id === 'velm-form') {
                flushRichTextEditors();
            }
        },
        true,
    );
}

function register() {
    if (typeof Alpine === 'undefined') {
        return;
    }
    Alpine.data('pvRichText', pvRichText);
}

document.addEventListener('alpine:init', register);
document.addEventListener('livewire:navigated', register);
document.addEventListener('DOMContentLoaded', () => hydrateRichTextDisplayImages());
document.addEventListener('livewire:navigated', () => hydrateRichTextDisplayImages());

if (typeof window.pvRegisterEditorWidgets === 'function') {
    window.pvRegisterEditorWidgets();
} else if (typeof Alpine !== 'undefined') {
    register();
}

if (document.readyState !== 'loading') {
    hydrateRichTextDisplayImages();
}
