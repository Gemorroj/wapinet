<?php

namespace Wapinet\TagBundle\Entity;

use FPN\TagBundle\Entity\Tag as BaseTag;
use Doctrine\ORM\Mapping as ORM;


/**
 * Tag
 *
 * @ORM\Table(name="tag")
 * @ORM\Entity
 */
class Tag extends BaseTag
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Tagging
     *
     * @ORM\OneToMany(targetEntity="Tagging", mappedBy="tag", fetch="EAGER")
     **/
    protected $tagging;
}
