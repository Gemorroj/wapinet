<?php

namespace Wapinet\NewsBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Wapinet\NewsBundle\Entity\News;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * News controller.
 *
 */
class NewsController extends Controller
{

    /**
     * Lists all News entities.
     *
     */
    public function indexAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();

        //$entities = $em->getRepository('WapinetNewsBundle:News')->findAll();
        //$q = $em->getRepository('WapinetNewsBundle:News');

        $queryBuilder = $em->createQueryBuilder()
            ->select('news')
            ->from('WapinetNewsBundle:News', 'news');
        $adapter = new DoctrineORMAdapter($queryBuilder);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(2);
        $pagerfanta->setCurrentPage($page);


        return $this->render('WapinetNewsBundle:News:index.html.twig', array(
                'pager' => $pagerfanta,
            ));
    }

    /**
     * Finds and displays a News entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('WapinetNewsBundle:News')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        return $this->render('WapinetNewsBundle:News:show.html.twig', array(
                'entity'      => $entity,
            ));
    }
}
