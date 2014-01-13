<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class GovnokodController extends Controller
{
    public function indexAction(Request $request)
    {
        $page = $request->get('page', 1);
        $govnokodManager = $this->getDoctrine()->getRepository('Wapinet\Bundle\Entity\Govnokod');
        $query = $govnokodManager->getListQuery();
        $pagerfanta = $this->get('paginate')->paginate($query, $page);

        return $this->render('WapinetBundle:Govnokod:index.html.twig', array(
            'pagerfanta' => $pagerfanta,
        ));
    }
}
