<?php

namespace WapinetBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use WapinetBundle\Entity\File;
use WapinetBundle\Entity\User;

class FileEvent extends Event
{
    const FILE_ADD = 'wapinet.file.add';

    private $user;
    private $file;

    /**
     * Constructs an event.
     *
     * @param User $user
     * @param File $file
     */
    public function __construct(User $user = null, File $file)
    {
        $this->user = $user;
        $this->file = $file;
    }

    /**
     * Returns the user for this event.
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Returns the file for this event.
     *
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }
}
