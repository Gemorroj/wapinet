<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WapinetBundle\Entity\News;

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
            ->getRepository(News::class)
            ->getAllBuilder();

        $pagerfanta = $this->get('paginate')->paginate($result, $page);

        return $this->render('@Wapinet/News/index.html.twig', array(
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
        return $this->render('@Wapinet/News/show.html.twig', array(
            'entity' => $news,
        ));
    }
}
