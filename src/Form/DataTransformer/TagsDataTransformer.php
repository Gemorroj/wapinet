<?php

namespace App\Form\DataTransformer;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\DataTransformerInterface;

final readonly class TagsDataTransformer implements DataTransformerInterface
{
    private const string SEPARATOR = ',';

    public function __construct(private TagRepository $tagRepository)
    {
    }

    /**
     * @param ArrayCollection<Tag>|null $value
     */
    public function transform(mixed $value): string
    {
        return $value ? \implode(self::SEPARATOR, $value->toArray()) : '';
    }

    /**
     * @param string $value
     *
     * @return ArrayCollection<Tag>|null
     */
    public function reverseTransform(mixed $value): ?ArrayCollection
    {
        $tags = \explode(self::SEPARATOR, $value);
        $tags = \array_map('trim', $tags);
        $tags = \array_filter($tags, static function (string $value): bool {
            return !empty($value);
        });

        $tags = \array_values($tags);

        return $this->tagRepository->makeTags($tags);
    }
}
