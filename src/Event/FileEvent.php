<?php

namespace App\Event;

use App\Entity\File;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class FileEvent extends Event
{
    public const FILE_ADD = 'wapinet.file.add';

    private $user;
    private $file;

    public function __construct(?User $user, File $file)
    {
        $this->user = $user;
        $this->file = $file;
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
    public function getFile(): File
    {
        return $this->file;
    }
}
