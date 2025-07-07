<?php

declare(strict_types=1);

namespace App\Views\Components;

use ViewComponents\Component;

class SeeMore extends Component
{
    public function render(): string
    {
        // SeeMore toujours expanded - pas de toggle "voir plus/voir moins"
        return <<<HTML
            <div class="see-more" style="--content-height: 10rem">
                <input id="see-more-checkbox" type="checkbox" class="see-more__checkbox" checked aria-hidden="true">
                <div class="mb-2 see-more__content {$this->class}"><div class="see-more_content-fade"></div>{$this->slot}</div>
            </div>
        HTML;
    }
}
