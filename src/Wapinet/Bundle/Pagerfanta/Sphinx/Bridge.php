<?php
namespace Wapinet\Bundle\Pagerfanta\Sphinx;

use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Pagerfanta\Pagerfanta;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pagerfanta Bridge
 *
 * @uses AbstractSphinxPager
 * @uses InterfaceSphinxPager
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Nikola Petkanski <nikola@petkanski.com>
 * @author Gemorroj <wapinet@mail.ru>
 */
class Bridge
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $repositoryClass;

    /**
     * @var string
     */
    protected $pkColumn = 'id';

    /**
     * @var QueryBuilder
     */
    protected $query = null;

    /**
     * The results obtained from sphinx
     *
     * @var array
     */
    protected $results;

    /**
     * The discriminator column name
     *
     * @var string
     */
    protected $discriminatorColumn = null;

    /**
     *
     * @var array Discriminator dependant repositories
     */
    protected $discriminatorRepositories = array();


    /**
     * Bridge constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em === null) {
            $this->em = $this->container->get('doctrine')->getManager();
        }

        return $this->em;
    }

    /**
     * @return array
     */
    protected function _extractPksFromResults()
    {
        $matches = isset($this->results['matches']) ? $this->results['matches'] : array();
        $pks = array();

        foreach ($matches as $match) {
            $pks[] = $match['id'];
        }

        return $pks;
    }

    /**
     * Sets the name of the entity manager which should be used to transform Sphinx results to entities.
     *
     * @param string $name
     *
     * @return Bridge
     *
     * @throws \LogicException
     */
    public function setEntityManagerByName($name)
    {
        return $this->setEntityManager($this->container->get('doctrine')->getManager($name));
    }

    /**
     * Sets the exact instance of entity manager which should be used to transform Sphinx results to entities.
     *
     * @param EntityManager $em
     *
     * @return Bridge
     */
    public function setEntityManager(EntityManager $em)
    {
        if ($this->em !== null) {
            throw new \LogicException('Entity manager can only be set before any results are fetched');
        }

        $this->em = $em;

        return $this;
    }

    /**
     * setRepositoryClass
     *
     * @param string $repositoryClass
     *
     * @return Bridge
     */
    public function setRepositoryClass($repositoryClass)
    {
        $this->repositoryClass = $repositoryClass;

        return $this;
    }

    /**
     * setPkColumn
     *
     * @param string $pkColumn
     *
     * @return Bridge
     */
    public function setPkColumn($pkColumn)
    {
        $this->pkColumn = $pkColumn;

        return $this;
    }

    /**
     * @param string $column
     * @return Bridge
     */
    public function setDiscriminatorColumn($column)
    {
        $this->discriminatorColumn = $column;

        return $this;
    }

    /**
     * @param mixed $discriminatorValue
     * @param string $repositoryClass
     * @param string $entityManager
     * @return Bridge
     */
    public function setDiscriminatorRepository($discriminatorValue, $repositoryClass, $entityManager = 'default')
    {
        $this->discriminatorRepositories[$discriminatorValue] = array(
            'class' => $repositoryClass,
            'em' => $entityManager
        );

        return $this;
    }

    /**
     *
     * @param array $repositories
     *
     * @return Bridge
     */
    public function setDiscriminatorRepositories(array $repositories)
    {
        foreach ($repositories as $discriminatorColumn => $data) {
            if (is_array($data)) {
                $params = array(
                    'discriminatorValue'    => $discriminatorColumn,
                    'class'                 => $data['class'],
                );

                if (key_exists('em', $data)) {
                    $params['em'] = $data['em'];
                }

                call_user_func_array(array($this, 'setDiscriminatorRepository'), $params);
            } else {
                $this->setDiscriminatorRepository($discriminatorColumn, $data);
            }
        }

        return $this;
    }

    /**
     * setSphinxResults
     *
     * @param ResultSetInterface $result
     *
     * @return Bridge
     */
    public function setSphinxResult($result)
    {
        $this->results = array(
            'matches'       => array(),
            'total_found'   => 0,
        );

        $totalFound = $result->count();

        $this->results['total_found'] += $totalFound;

        if ($totalFound > 0) {
            $this->results['matches'] = array_merge(
                $this->results['matches'],
                $result->fetchAllAssoc()
            );
        }

        return $this;
    }

    /**
     * Returns an instance of the pager
     *
     * @return Pagerfanta
     */
    public function getPager()
    {
        $hasDiscriminator = $this->discriminatorColumn !== null;
        $hasRepositoryClass = $this->repositoryClass !== null;

        if (!$hasRepositoryClass && !$hasDiscriminator) {
            throw new \RuntimeException('You should define either a repository class, either discriminator');
        }

        if (is_null($this->results)) {
            throw new \RuntimeException('You should define sphinx results on '.__CLASS__);
        }

        $results = $hasDiscriminator ? $this->getDiscriminatorResults() : $this->getResults();

        $adapter = new Adapter($results);

        $adapter->setNbResults(isset($this->results['total_found']) ? $this->results['total_found'] : 0);

        return new Pagerfanta($adapter);
    }

    /**
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    protected function getDiscriminatorResults()
    {
        $rawResults = $this->results;

        if (empty($rawResults) || empty($rawResults['matches'])) {
            return array();
        }

        $results = array();
        $usedDiscriminators = array();

        /**
         * Collect discriminators and their records
         */
        foreach ($rawResults['matches'] as $row) {
            $id = $row['id'];

            if (!key_exists($this->discriminatorColumn, $row['attrs'])) {
                throw new \UnexpectedValueException('Missing discriminator column in sphinx result entry');
            }

            $rowDiscriminator = $row['attrs'][$this->discriminatorColumn];

            if (!key_exists($rowDiscriminator, $usedDiscriminators)) {
                $usedDiscriminators[$rowDiscriminator] = array();
            }

            $results[$id] = null;
            $usedDiscriminators[$rowDiscriminator][$id] = $row;
        }

        /**
         * Fetchs the results for each discriminator used and populate the $results array,
         * which contains the proper order of items as returned by Sphinx
         */
        foreach ($usedDiscriminators as $discriminatorValue => $discriminatorResults) {
            $qb = $this->getDiscriminatorQuery($discriminatorValue);

            $primaryKeys = array_keys($discriminatorResults);

            $query = $qb->where($qb->expr()->in('r.'.$this->pkColumn, $primaryKeys))->getQuery();

            foreach ($query->execute() as $id => $entity) {
                $results[$id] = $entity;
            }
        }

        return $results;
    }

    /**
     * @return array
     */
    protected function getResults()
    {
        $pks = $this->_extractPksFromResults();

        $results = array();

        if (false === empty($pks)) {
            $qb = $this->getQuery();

            $qb = $qb->where($qb->expr()->in('r.'.$this->pkColumn, $pks))
                //->addOrderBy('FIELD(r.id,...)', 'ASC')
                ->getQuery();
            //@todo watching on doctrine FIELD extension ... we cannot use it natively . . . .

            $unordoredResults = $qb->getResult();

            foreach ($pks as $pk) {
                if (isset($unordoredResults[$pk])) {
                    $results[$pk] = $unordoredResults[$pk];
                }
            }
        }

        return $results;
    }

    /**
     * @param mixed $discriminatorValue
     * @return QueryBuilder
     */
    public function getDiscriminatorQuery($discriminatorValue)
    {
        $discriminatorData = $this->discriminatorRepositories[$discriminatorValue];

        $em = $this->container->get('doctrine')->getManager($discriminatorData['em']);
        /* @var $em EntityManager */

        $qb = $em->createQueryBuilder();

        $repositoryClass = $discriminatorData['class'];

        return $qb->select('r') ->from($repositoryClass, sprintf('r INDEX BY r.%s', $this->pkColumn));
    }

    /**
     * @return QueryBuilder query
     */
    public function getDefaultQuery()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        return $qb->select('r') ->from($this->repositoryClass, sprintf('r INDEX BY r.%s', $this->pkColumn));

    }

    /**
     * @param QueryBuilder|null $query
     * @return Bridge
     */
    public function setQuery(QueryBuilder $query = null)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * setDefaultQuery
     *
     * @return QueryBuilder
     */
    public function getQuery()
    {
        if ($this->query == null) {
            return $this->getDefaultQuery();
        }

        return $this->query;
    }
}
