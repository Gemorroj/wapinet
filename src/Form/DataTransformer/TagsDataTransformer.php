<?php

namespace App\Form\DataTransformer;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

class TagsDataTransformer implements DataTransformerInterface
{
    private string $separator = ',';
    private TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
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
     *
     * @return ArrayCollection|null
     */
    public function reverseTransform($tagsDataFromForm)
    {
        $tags = \explode($this->separator, $tagsDataFromForm);
        $tags = \array_map('trim', $tags);
        $tags = \array_filter($tags, static function ($value): bool {
            return !empty($value);
        });

        $tags = \array_values($tags);

        return $this->tagRepository->makeTags($tags);
    }
}
