<?php

namespace App\Controller;

use App\Helper\Bash;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BashController extends Controller
{
    /**
     * @param Request $request
     * @param Bash $bashHelper
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, Bash $bashHelper)
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
