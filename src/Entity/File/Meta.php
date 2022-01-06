<?php

namespace App\Entity\File;

/**
 * Meta.
 */
class Meta
{
    /**
     * @var array
     */
    protected $meta = [];

    /**
     * @param string $key
     */
    public function has($key): bool
    {
        return isset($this->meta[$key]);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        return $this->meta[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Meta
     */
    public function set($key, $value)
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function __toString(): string
    {
        $out = '';
        foreach ($this->meta as $key => $value) {
            $out .= $key.': '.$value."\n";
        }

        return $out;
    }
}
