<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GuestbookController extends Controller
{
    public function indexAction()
    {
        return $this->render('WapinetBundle:Guestbook:index.html.twig');
    }
}
