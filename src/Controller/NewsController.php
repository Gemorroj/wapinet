<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\Paginate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/news')]
class NewsController extends AbstractController
{
    #[Route(path: '', name: 'news_index')]
    public function indexAction(Request $request, Paginate $paginate, NewsRepository $newsRepository): Response
    {
        $page = $request->get('page', 1);

        $result = $newsRepository->getAllBuilder();

        $pagerfanta = $paginate->paginate($result, $page);

        return $this->render('News/index.html.twig', [
            'pagerfanta' => $pagerfanta,
        ]);
    }

    #[Route(path: '/{id}', name: 'news_show', requirements: ['id' => '\d+'])]
    public function showAction(News $news): Response
    {
        return $this->render('News/show.html.twig', [
            'entity' => $news,
        ]);
    }
}
