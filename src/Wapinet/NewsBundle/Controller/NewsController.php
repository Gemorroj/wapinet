<?php

namespace Wapinet\NewsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * News controller.
 *
 */
class NewsController extends Controller
{
    /**
     * Lists all News entities.
     *
     * @param int $page
     * @return Response
     */
    public function indexAction($page = 1)
    {
        $result = $this->getDoctrine()
            ->getRepository('WapinetNewsBundle:News')
            ->getAllBuilder();

        $result = $this->get('paginate')->paginate($result, $page);

        return $this->render('WapinetNewsBundle:News:index.html.twig', array(
            'news' => $result,
        ));
    }

    /**
     * Finds and displays a News entity.
     *
     * @param int $id
     * @throws NotFoundHttpException
     * @return Response
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('WapinetNewsBundle:News')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        return $this->render('WapinetNewsBundle:News:show.html.twig', array(
            'entity' => $entity,
            'comments_id' => 'news-' . $id
        ));
    }
}
