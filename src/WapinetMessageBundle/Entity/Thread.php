<?php

namespace WapinetMessageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\MessageBundle\Entity\Thread as BaseThread;

/**
 * @ORM\Entity
 * @ORM\Table("message_thread")
 */
class Thread extends BaseThread
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="WapinetBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $createdBy;

    /**
     * @ORM\OneToMany(
     *   targetEntity="WapinetMessageBundle\Entity\Message",
     *   mappedBy="thread", fetch="EXTRA_LAZY", orphanRemoval=true
     * )
     * @ORM\OrderBy({"id" = "DESC"})
     * @var Message[]|\Doctrine\Common\Collections\Collection
     */
    protected $messages;

    /**
     * @ORM\OneToMany(
     *   targetEntity="WapinetMessageBundle\Entity\ThreadMetadata",
     *   mappedBy="thread",
     *   cascade={"all"}
     * )
     * @var ThreadMetadata[]|\Doctrine\Common\Collections\Collection
     */
    protected $metadata;


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getSubject();
    }
}
