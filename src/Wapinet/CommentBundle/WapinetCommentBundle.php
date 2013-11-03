<?php

namespace Wapinet\CommentBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WapinetCommentBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSCommentBundle';
    }
}
