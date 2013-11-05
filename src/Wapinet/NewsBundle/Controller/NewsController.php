<?php

namespace Wapinet\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


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
        $result = $this->getDoctrine()
            ->getRepository('WapinetNewsBundle:News')
            ->getPages($page);


        return $this->render('WapinetNewsBundle:News:index.html.twig', array(
                'pager' => $result,
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
