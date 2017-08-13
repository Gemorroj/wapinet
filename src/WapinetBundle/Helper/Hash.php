<?php
namespace WapinetBundle\Helper;

/**
 * Hash хэлпер
 */
class Hash
{
    /**
     * @return array
     */
    public function getAlgorithms()
    {
        return \hash_algos();
    }


    /**
     * @param string $algorithm
     * @param string $string
     * @return string
     */
    public function hashString($algorithm, $string)
    {
        return \hash($algorithm, $string);
    }

    /**
     * @param string $algorithm
     * @param string $fileName
     * @return string
     */
    public function hashFile($algorithm, $fileName)
    {
        return \hash_file($algorithm, $fileName);
    }
}
