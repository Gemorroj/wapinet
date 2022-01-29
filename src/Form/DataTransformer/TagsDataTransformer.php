<?php

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

class TagsDataTransformer implements DataTransformerInterface
{
    private const SEPARATOR = ',';

    public function __construct(private TagRepository $tagRepository)
    {
    }

    /**
     * @param ArrayCollection<Tag>|null $tagsDataFromDb
     *
     * @return string
     */
    public function transform($tagsDataFromDb)
    {
        return $tagsDataFromDb ? \implode(self::SEPARATOR, $tagsDataFromDb->toArray()) : '';
    }

    /**
     * @param string $tagsDataFromForm
     *
     * @return ArrayCollection<Tag>|null
     */
    public function reverseTransform($tagsDataFromForm)
    {
        $tags = \explode(self::SEPARATOR, $tagsDataFromForm);
        $tags = \array_map('trim', $tags);
        $tags = \array_filter($tags, static function ($value): bool {
            return !empty($value);
        });

        $tags = \array_values($tags);

        return $this->tagRepository->makeTags($tags);
    }
}
