<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * News controller.
 */
class NewsController extends Controller
{
    /**
     * Lists all News entities.
     *
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $result = $this->getDoctrine()
            ->getRepository('WapinetBundle:News')
            ->getAllBuilder();

        $pagerfanta = $this->get('paginate')->paginate($result, $page);

        return $this->render('WapinetBundle:News:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
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

        $entity = $em->getRepository('WapinetBundle:News')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find News entity.');
        }

        return $this->render('WapinetBundle:News:show.html.twig', array(
            'entity' => $entity,
            'comments_id' => 'news-' . $id
        ));
    }
}
