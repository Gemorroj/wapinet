<?php

declare(strict_types=1);

namespace App\Pagerfanta;

use Pagerfanta\View\Template\DefaultTemplate;

final class Template extends DefaultTemplate
{
    protected function getDefaultOptions(): array
    {
        return [
            'prev_message' => 'Пред.',
            'next_message' => 'След.',
            'dots_message' => '...',
            'active_suffix' => '',
            'css_active_class' => 'page-current pagination__item--current-page',
            'css_container_class' => 'pagination',
            'css_disabled_class' => 'pagination__item--disabled',
            'css_dots_class' => 'pagination__item--separator',
            'css_item_class' => 'pagination__item',
            'css_prev_class' => 'pagination__item--previous-page',
            'css_next_class' => 'pagination__item--next-page',
            'container_template' => '<nav data-role="controlgroup" data-type="horizontal">%%pages%%</nav>',
            'rel_previous' => 'prev',
            'rel_next' => 'next',
            'page_template' => '<a href="%href%"%rel% data-role="button">%text%</a>',
            'span_template' => '<a href="#" class="ui-disabled %class%" data-role="button">%text%</a>',
        ];
    }
}
