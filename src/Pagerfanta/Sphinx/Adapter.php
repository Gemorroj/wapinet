<?php
namespace App\Pagerfanta\Sphinx;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Sphinx Adapter
 *
 * @uses AdapterInterface
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Gemorroj <wapinet@mail.ru>
 */
class Adapter implements AdapterInterface
{
    /**
     * @var array
     */
    protected $array = [];
    /**
     * @var integer
     */
    protected $nbResults = 0;

    /**
     * Constructor.
     *
     * @param array $array The array.
     *
     * @api
     */
    public function __construct(array $array = null)
    {
        $this->array = $array;
    }

    /**
     * Returns the array.
     *
     * @return array The array.
     *
     * @api
     */
    public function getArray()
    {
        return $this->array;
    }

    /**
     * @param array $array
     *
     * @return Adapter
     */
    public function setArray(array $array)
    {
        $this->array = $array;

        return $this;
    }

    /**
     * get nb results
     */
    public function getNbResults()
    {
        return $this->nbResults;
    }

    /**
     * setNbResults
     *
     * @param int $v nbOfResults
     * @return Adapter
     */
    public function setNbResults($v)
    {
        $this->nbResults = $v;

        return $this;
    }

    /**
     * @param int $offset
     * @param int $length
     * @return array
     */
    public function getSlice($offset, $length)
    {
        if ($offset >= \count($this->array)) {
            return $this->array;
        }
        return \array_slice($this->array, $offset, $length);
    }
}
