<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected string $view = 'filament.widgets.account-widget';

    /** @phpstan-ignore-next-line */
    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 'full',
    ];

    protected static ?int $sort = 0;
}
