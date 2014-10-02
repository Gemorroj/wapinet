<?php

namespace Wapinet\Bundle\Entity;

use Symfony\Component\HttpFoundation\Request;

/**
 * Online
 */
class Online
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $datetime;

    /**
     * @var string
     */
    private $ip;

    /**
     * @var string
     */
    private $browser;

    /**
     * @var Request
     */
    private $request;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Online
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return Online
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }


    /**
     * Set datetime
     *
     * @param \DateTime $datetime
     * @return Online
     */
    public function setDatetime(\DateTime $datetime)
    {
        $this->datetime = $datetime;
    
        return $this;
    }

    /**
     * Get datetime
     *
     * @return \DateTime 
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return Online
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    
        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set browser
     *
     * @param string $browser
     * @return Online
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;
    
        return $this;
    }

    /**
     * Get browser
     *
     * @return string 
     */
    public function getBrowser()
    {
        return $this->browser;
    }
}
