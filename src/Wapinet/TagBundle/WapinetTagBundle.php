<?php

namespace Wapinet\TagBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetTagBundle extends Bundle
{
    public function getParent()
    {
        return 'FPNTagBundle';
    }
}
