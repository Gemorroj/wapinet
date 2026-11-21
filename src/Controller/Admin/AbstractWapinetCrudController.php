<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * Common defaults for Wapinet CRUD controllers: Russian entity labels and
 * newest-first sorting. Individual controllers only add search and fields.
 */
abstract class AbstractWapinetCrudController extends AbstractCrudController
{
    abstract protected function singularLabel(): string;

    abstract protected function pluralLabel(): string;

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular($this->singularLabel())
            ->setEntityLabelInPlural($this->pluralLabel())
            ->setDefaultSort(['id' => 'DESC']);
    }
}
