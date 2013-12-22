<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class BashController extends Controller
{
    public function indexAction(Request $request)
    {
        $page = $request->get('page');
        $bashHelper = $this->get('bash');
        $items = $bashHelper->getPage($page);

        $response = $this->render('WapinetBundle:Bash:index.html.twig', array(
            'pagerfanta' => $items,
        ));
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
