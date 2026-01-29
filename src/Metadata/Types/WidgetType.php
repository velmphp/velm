<?php

namespace Velm\Core\Metadata\Types;

enum WidgetType: string
{
    case INPUT = 'input';
    case SELECT = 'select';
    case CHECKBOX = 'checkbox';

    case CHECKBOXLIST = 'checkboxlist';

    case RADIO = 'radio';
    case DATETIMEPICKER = 'datetimepicker';

    case FILE_UPLOAD = 'fileupload';

    case RICH_EDITOR = 'richeditor';

    case MARKDOWN_EDITOR = 'markdowneditor';

    case REPEATER = 'repeater';

    case BUILDER = 'builder';

    case TAGSINPUT = 'tagsinput';

    case TEXTAREA = 'textarea';

    case KEY_VALUE = 'keyvalue';

    case COLOR_PICKER = 'colorpicker';

    case TOGGLE_BUTTONS = 'togglebuttons';

    case SLIDER = 'slider';

    case CODE_EDITOR = 'codeeditor';

    case HIDDEN = 'hidden';

    case CUSTOM = 'custom';
}
