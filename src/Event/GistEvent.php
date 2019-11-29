<?php

namespace App\Event;

use App\Entity\Gist;
use App\Entity\User;
use Symfony\Component\EventDispatcher\Event;

class GistEvent extends Event
{
    const GIST_ADD = 'wapinet.gist.add';

    private $user;
    private $gist;

    /**
     * Constructs an event.
     */
    public function __construct(?User $user = null, Gist $gist)
    {
        $this->user = $user;
        $this->gist = $gist;
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
