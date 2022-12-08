<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Gist;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class GistEvent extends Event
{
    public const GIST_ADD = 'wapinet.gist.add';

    public function __construct(private ?User $user, private Gist $gist)
    {
    }

    /**
     * Returns the user for this event.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Returns the file for this event.
     */
    public function getGist(): Gist
    {
        return $this->gist;
    }
}
