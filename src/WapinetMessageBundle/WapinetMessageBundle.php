<?php

namespace WapinetMessageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetMessageBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSMessageBundle';
    }
}
