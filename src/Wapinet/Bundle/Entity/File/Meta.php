<?php

namespace Wapinet\Bundle\Entity\File;


/**
 * Meta
 */
class Meta
{
    /**
     * @var array
     */
    protected $meta = array();

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->meta[$key]);
    }


    /**
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }


    /**
     * @param string $key
     * @param string $value
     * @return Meta
     */
    public function set($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }
}