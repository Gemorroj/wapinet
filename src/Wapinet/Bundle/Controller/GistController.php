<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class GistController extends Controller
{
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $gistManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Gist');
        $query = $gistManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Gist:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }
}
