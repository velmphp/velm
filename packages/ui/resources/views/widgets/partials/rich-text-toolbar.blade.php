<div
    class="tiptap-toolbar"
    data-variant="fixed"
    role="toolbar"
    aria-label="{{ __('Formatting') }}"
>
    <div class="tiptap-toolbar-spacer" aria-hidden="true"></div>

    <div class="tiptap-toolbar-group">
        <button
            type="button"
            class="tiptap-button"
            data-style="ghost"
            :disabled="!canUndo()"
            @mousedown.prevent.stop="run('undo')"
            title="{{ __('Undo') }}"
            aria-label="{{ __('Undo') }}"
        >
            @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'undo'])
        </button>
        <button
            type="button"
            class="tiptap-button"
            data-style="ghost"
            :disabled="!canRedo()"
            @mousedown.prevent.stop="run('redo')"
            title="{{ __('Redo') }}"
            aria-label="{{ __('Redo') }}"
        >
            @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'redo'])
        </button>
    </div>

    <span class="tiptap-separator" aria-hidden="true"></span>

    <div class="tiptap-toolbar-group">
        <div class="tiptap-toolbar-dropdown" @click.outside="headingMenuOpen = false">
            <button
                type="button"
                class="tiptap-button"
                data-style="ghost"
                x-ref="headingTrigger"
                :data-active-state="isActive('heading') ? 'on' : 'off'"
                @click.stop="toggleHeadingMenu()"
                title="{{ __('Heading') }}"
                aria-label="{{ __('Heading') }}"
                aria-haspopup="menu"
                :aria-expanded="headingMenuOpen"
            >
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'heading'])
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'chevron-down'])
            </button>
            <div x-ref="headingMenu" class="tiptap-dropdown-menu" x-show="headingMenuOpen" x-cloak role="menu">
                <button
                    type="button"
                    class="tiptap-dropdown-menu__item"
                    role="menuitem"
                    :data-active-state="isActive('paragraph') ? 'on' : 'off'"
                    @mousedown.prevent.stop="run('paragraph')"
                >
                    {{ __('Normal') }}
                </button>
                @foreach ([1 => __('Heading 1'), 2 => __('Heading 2'), 3 => __('Heading 3'), 4 => __('Heading 4')] as $level => $label)
                    <button
                        type="button"
                        class="tiptap-dropdown-menu__item"
                        role="menuitem"
                        :data-active-state="isActive('h{{ $level }}') ? 'on' : 'off'"
                        @mousedown.prevent.stop="run('h{{ $level }}')"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="tiptap-toolbar-dropdown" @click.outside="listMenuOpen = false">
            <button
                type="button"
                class="tiptap-button"
                data-style="ghost"
                x-ref="listTrigger"
                :data-active-state="isActive('list') ? 'on' : 'off'"
                @click.stop="toggleListMenu()"
                title="{{ __('Lists') }}"
                aria-label="{{ __('Lists') }}"
                aria-haspopup="menu"
                :aria-expanded="listMenuOpen"
            >
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'list'])
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'chevron-down'])
            </button>
            <div x-ref="listMenu" class="tiptap-dropdown-menu" x-show="listMenuOpen" x-cloak role="menu">
                <button type="button" class="tiptap-dropdown-menu__item" role="menuitem" :data-active-state="isActive('bullet') ? 'on' : 'off'" @mousedown.prevent.stop="run('bullet')">
                    @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'list-bullet', 'class' => 'tiptap-dropdown-menu__icon'])
                    <span>{{ __('Bullet list') }}</span>
                </button>
                <button type="button" class="tiptap-dropdown-menu__item" role="menuitem" :data-active-state="isActive('ordered') ? 'on' : 'off'" @mousedown.prevent.stop="run('ordered')">
                    @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'list-ordered', 'class' => 'tiptap-dropdown-menu__icon'])
                    <span>{{ __('Numbered list') }}</span>
                </button>
                <button type="button" class="tiptap-dropdown-menu__item" role="menuitem" :data-active-state="isActive('task') ? 'on' : 'off'" @mousedown.prevent.stop="run('task')">
                    @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'list-todo', 'class' => 'tiptap-dropdown-menu__icon'])
                    <span>{{ __('Task list') }}</span>
                </button>
            </div>
        </div>

        <button
            type="button"
            class="tiptap-button"
            data-style="ghost"
            :data-active-state="isActive('blockquote') ? 'on' : 'off'"
            @mousedown.prevent.stop="run('blockquote')"
            title="{{ __('Blockquote') }}"
            aria-label="{{ __('Blockquote') }}"
        >
            @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'blockquote'])
        </button>
        <button
            type="button"
            class="tiptap-button"
            data-style="ghost"
            :data-active-state="isActive('codeBlock') ? 'on' : 'off'"
            @mousedown.prevent.stop="run('codeBlock')"
            title="{{ __('Code block') }}"
            aria-label="{{ __('Code block') }}"
        >
            @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'code-block'])
        </button>
    </div>

    <span class="tiptap-separator" aria-hidden="true"></span>

    <div class="tiptap-toolbar-group">
        @php
            $marks = [
                'bold' => __('Bold'),
                'italic' => __('Italic'),
                'strike' => __('Strikethrough'),
                'code' => __('Code'),
                'underline' => __('Underline'),
                'highlight' => __('Highlight'),
                'link' => __('Link'),
            ];
        @endphp
        @foreach ($marks as $mark => $markLabel)
            <button
                type="button"
                class="tiptap-button"
                data-style="ghost"
                :data-active-state="isActive('{{ $mark }}') ? 'on' : 'off'"
                @mousedown.prevent.stop="run('{{ $mark }}')"
                title="{{ $markLabel }}"
                aria-label="{{ $markLabel }}"
            >
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => $mark])
            </button>
        @endforeach
    </div>

    <span class="tiptap-separator" aria-hidden="true"></span>

    <div class="tiptap-toolbar-group">
        <button type="button" class="tiptap-button" data-style="ghost" :data-active-state="isActive('superscript') ? 'on' : 'off'" @mousedown.prevent.stop="run('superscript')" title="{{ __('Superscript') }}" aria-label="{{ __('Superscript') }}">
            <span class="tiptap-button-mark-label">x<sup>2</sup></span>
        </button>
        <button type="button" class="tiptap-button" data-style="ghost" :data-active-state="isActive('subscript') ? 'on' : 'off'" @mousedown.prevent.stop="run('subscript')" title="{{ __('Subscript') }}" aria-label="{{ __('Subscript') }}">
            <span class="tiptap-button-mark-label">x<sub>2</sub></span>
        </button>
    </div>

    <span class="tiptap-separator" aria-hidden="true"></span>

    <div class="tiptap-toolbar-group">
        @php
            $alignments = [
                'align-left' => __('Align left'),
                'align-center' => __('Align center'),
                'align-right' => __('Align right'),
                'align-justify' => __('Justify'),
            ];
        @endphp
        @foreach ($alignments as $icon => $alignLabel)
            <button
                type="button"
                class="tiptap-button"
                data-style="ghost"
                :data-active-state="isActive('{{ $icon }}') ? 'on' : 'off'"
                @mousedown.prevent.stop="run('{{ $icon }}')"
                title="{{ $alignLabel }}"
                aria-label="{{ $alignLabel }}"
            >
                @include('velm-ui::widgets.partials.tiptap-icon', ['name' => $icon])
            </button>
        @endforeach
    </div>

    <span class="tiptap-separator" aria-hidden="true"></span>

    <div class="tiptap-toolbar-group">
        <button
            type="button"
            class="tiptap-button"
            data-style="ghost"
            @mousedown.prevent.stop="pickImage()"
            title="{{ __('Add image') }}"
            aria-label="{{ __('Add image') }}"
        >
            @include('velm-ui::widgets.partials.tiptap-icon', ['name' => 'image'])
        </button>
    </div>

    <div class="tiptap-toolbar-spacer" aria-hidden="true"></div>
</div>
