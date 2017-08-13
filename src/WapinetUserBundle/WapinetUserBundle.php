<?php

namespace WapinetUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
