<?php

namespace Wapinet\MessageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetMessageBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSMessageBundle';
    }
}
