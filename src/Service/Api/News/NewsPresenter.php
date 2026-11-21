<?php

declare(strict_types=1);

namespace App\Service\Api\News;

use App\Dto\Api\News\NewsListItemResponse;
use App\Dto\Api\News\NewsResponse;
use App\Dto\Api\User\UserSummary;
use App\Entity\News;
use App\Service\Bbcode;

final readonly class NewsPresenter
{
    public function __construct(private Bbcode $bbcode)
    {
    }

    public function listItem(News $news): NewsListItemResponse
    {
        return new NewsListItemResponse(
            (int) $news->getId(),
            $news->getSubject(),
            $news->getCreatedAt(),
            $news->getUpdatedAt(),
        );
    }

    public function news(News $news): NewsResponse
    {
        $createdBy = $news->getCreatedBy();

        return new NewsResponse(
            (int) $news->getId(),
            $news->getSubject(),
            $news->getBody(),
            $this->bbcode->render($news->getBody()),
            $news->getCreatedAt(),
            $news->getUpdatedAt(),
            null !== $createdBy ? UserSummary::fromUser($createdBy) : null,
        );
    }
}
