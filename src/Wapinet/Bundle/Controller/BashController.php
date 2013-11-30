<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BashController extends Controller
{
    public function indexAction($page = null)
    {
        $bashHelper = $this->get('bash');
        $page = $bashHelper->getPage($page);

        $response = $this->render('WapinetBundle:Bash:index.html.twig', array(
            'items' => $page,
        ));
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }
}
