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
     *
     * @param User $user
     * @param Gist $gist
     */
    public function __construct(User $user = null, Gist $gist)
    {
        $this->user = $user;
        $this->gist = $gist;
    }

    /**
     * Returns the user for this event.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Returns the file for this event.
     *
     * @return Gist
     */
    public function getGist()
    {
        return $this->gist;
    }
}