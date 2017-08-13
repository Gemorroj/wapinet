<?php

namespace WapinetMessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Entity\ThreadMetadata as BaseThreadMetadata;
use FOS\MessageBundle\Model\ParticipantInterface;
use FOS\MessageBundle\Model\ThreadInterface;

/**
 * @ORM\Entity
 * @ORM\Table("message_thread_metadata")
 */
class ThreadMetadata extends BaseThreadMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(
     *   targetEntity="WapinetMessageBundle\Entity\Thread",
     *   inversedBy="metadata"
     * )
     * @var ThreadInterface
     */
    protected $thread;

    /**
     * @ORM\ManyToOne(targetEntity="WapinetUserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @var ParticipantInterface
     */
    protected $participant;


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getThread()->getSubject();
    }
}
