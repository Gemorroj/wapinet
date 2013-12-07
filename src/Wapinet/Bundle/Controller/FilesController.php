<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @see http://wap4file.org
 */
class FilesController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Files:index.html.twig');
    }

    public function informationAction()
    {
        return $this->render('WapinetBundle:Files:information.html.twig');
    }

    public function statisticsAction()
    {
        return $this->render('WapinetBundle:Files:statistics.html.twig');
    }
}
