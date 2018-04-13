<?php

namespace App\Pagerfanta;

use Pagerfanta\View\Template\DefaultTemplate;

class Template extends DefaultTemplate
{
    static protected $defaultOptions = [
        'previous_message'   => 'Пред.',
        'next_message'       => 'След.',
        'css_disabled_class' => '',
        'css_dots_class'     => '',
        'css_current_class'  => 'page-current',
        'dots_text'          => '...',
        'container_template' => '<nav data-role="controlgroup" data-type="horizontal">%pages%</nav>',
        'page_template'      => '<a href="%href%" data-role="button">%text%</a>',
        'span_template'      => '<a href="#" class="ui-disabled %class%" data-role="button">%text%</a>',
        'rel_previous'       => 'prev',
        'rel_next'           => 'next',
    ];
}
