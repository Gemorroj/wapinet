<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        /** @var NewsRepository $repository */
        $repository = $this->getDoctrine()->getRepository(News::class);
        $result = $repository->getAllBuilder();

        $pagerfanta = $this->get('paginate')->paginate($result, $page);

        return $this->render('News/index.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    /**
     * Finds and displays a News entity.
     *
     * @param News $news
     * @return Response
     */
    public function showAction(News $news)
    {
        return $this->render('News/show.html.twig', [
            'entity' => $news,
        ]);
    }
}
