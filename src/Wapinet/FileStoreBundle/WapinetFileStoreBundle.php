<?php

namespace Wapinet\FileStoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetFileStoreBundle extends Bundle
{
    public function getParent()
    {
        return 'IphpFileStoreBundle';
    }
}
