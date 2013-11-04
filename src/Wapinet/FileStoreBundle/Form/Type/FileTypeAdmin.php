<?php

namespace Wapinet\FileStoreBundle\Form\Type;

use Iphp\FileStoreBundle\Form\Type\FileType as BaseType;

/**
 * @author Vitiko <vitiko@mail.ru>
 */
class FileTypeAdmin extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'iphp_file_admin';
    }
}
