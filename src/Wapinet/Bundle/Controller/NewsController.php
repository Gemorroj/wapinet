<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wapinet\Bundle\Entity\News;

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
     * @param News $news
     * @return Response
     */
    public function showAction(News $news)
    {
        return $this->render('WapinetBundle:News:show.html.twig', array(
            'entity' => $news,
            'comments_id' => 'news-' . $news->getId(),
        ));
    }
}
