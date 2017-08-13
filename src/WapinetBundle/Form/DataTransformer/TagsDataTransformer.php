<?php

namespace WapinetBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use WapinetBundle\Entity\Tag;

class TagsDataTransformer implements DataTransformerInterface
{
    private $separator = ',';
    private $entityManager;


    /**
     * TagsType constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }


    /**
     * @param ArrayCollection|null $tagsDataFromDb
     *
     * @return string
     */
    public function transform($tagsDataFromDb)
    {
        return $tagsDataFromDb ? \implode($this->separator, $tagsDataFromDb->toArray()) : '';
    }


    /**
     * @param string $tagsDataFromForm
     * @return ArrayCollection|null
     */
    public function reverseTransform($tagsDataFromForm)
    {
        $tags = \explode($this->separator, $tagsDataFromForm);
        $tags = \array_map('trim', $tags);
        $tags = \array_filter($tags, function ($value) {
            return !empty($value);
        });

        $tags = \array_values($tags);

        $tagManager = $this->entityManager->getRepository(Tag::class);

        return $tagManager->makeTags($tags);
    }
}
