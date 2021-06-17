<?php

namespace App\Pagerfanta;

use Pagerfanta\View\DefaultView;
use Pagerfanta\View\Template\TemplateInterface;

class View extends DefaultView
{
    protected function createDefaultTemplate(): TemplateInterface
    {
        return new Template();
    }
}
