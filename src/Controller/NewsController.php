<?php

namespace App\Controller;

use App\Entity\News;
use App\Helper\Paginate;
use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * News controller.
 */
class NewsController extends AbstractController
{
    /**
     * Lists all News entities.
     *
     * @param Request  $request
     * @param Paginate $paginate
     *
     * @return Response
     */
    public function indexAction(Request $request, Paginate $paginate): Response
    {
        $page = $request->get('page', 1);

        /** @var NewsRepository $repository */
        $repository = $this->getDoctrine()->getRepository(News::class);
        $result = $repository->getAllBuilder();

        $pagerfanta = $paginate->paginate($result, $page);

        return $this->render('News/index.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    /**
     * Finds and displays a News entity.
     *
     * @param News $news
     *
     * @return Response
     */
    public function showAction(News $news): Response
    {
        return $this->render('News/show.html.twig', [
            'entity' => $news,
        ]);
    }
}
