<?php

namespace App\Controller;

use App\Service\Bash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bash')]
class BashController extends AbstractController
{
    #[Route(path: '', name: 'bash_index')]
    public function indexAction(Request $request, Bash $bashHelper): Response
    {
        $page = $request->get('page');
        $items = $bashHelper->getPage($page);

        $response = $this->render('Bash/index.html.twig', [
            'pagerfanta' => $items,
        ]);
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
