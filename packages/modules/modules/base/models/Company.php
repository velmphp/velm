<?php

declare(strict_types=1);

namespace Velm\Modules\Base\Models;

use Velm\Fields\BooleanField;
use Velm\Fields\CharField;
use Velm\Fields\IntegerField;
use Velm\Fields\TextField;
use Velm\Models\Model;

class Company extends Model
{
    protected static ?string $name = 'res.company';

    protected static ?string $table = 'res_company';

    public static function defineFields(): array
    {
        return [
            'name' => CharField::make()->required()->label('Name'),
            'active' => BooleanField::make()->default(true)->label('Active'),
            'timezone' => CharField::make()->default('UTC')->label('Timezone'),
            'primary_color' => CharField::make()->label('Primary color'),
            'font_family' => CharField::make()->label('Font family'),
            'app_name' => CharField::make()->label('Application name'),
            'app_tagline' => CharField::make()->label('Tagline'),
            'logo_url' => CharField::make()->label('Logo URL (light)'),
            'logo_url_dark' => CharField::make()->label('Logo URL (dark)'),
            'header_logo_height' => IntegerField::make()->default(0)->label('Header logo height (px)'),
            'show_header_brand_text' => BooleanField::make()->default(true)->label('Show app name in header'),
            'favicon_url' => CharField::make()->label('Favicon URL'),
            'copyright_text' => CharField::make()->label('Copyright'),
            'support_email' => CharField::make()->label('Support email'),
            'support_url' => CharField::make()->label('Support URL'),
            'show_powered_by' => BooleanField::make()->default(true)->label('Show powered by Velm'),
            'menu_layout' => CharField::make()->label('Navigation layout'),
        ];
    }
}
