<?php

namespace Wapinet\Bundle\Entity;
/**
 * Autocomplete
 */
class Autocomplete
{

    /**
     * @var string
     */
    public $value;
    /**
     * @var string
     */
    public $label;

    /**
     * @param string $value
     * @param string $label
     */
    public function __construct($value, $label)
    {
        $this->value = $value;
        $this->label = $label;
    }
}
