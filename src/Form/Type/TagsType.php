<?php

namespace App\Form\Type;

use App\Form\DataTransformer\TagsDataTransformer;
use App\Repository\TagRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TagsType extends AbstractType
{
    public function __construct(private TagRepository $tagRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addViewTransformer(new TagsDataTransformer($this->tagRepository));
    }

    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'tags';
    }
}
