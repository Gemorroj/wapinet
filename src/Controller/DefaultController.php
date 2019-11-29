<?php

namespace App\Controller;

use App\Entity\Online;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function indexAction(): Response
    {
        return $this->render('Default/index.html.twig');
    }

    public function aboutAction(): Response
    {
        return $this->render('Default/about.html.twig');
    }

    public function onlineAction(): Response
    {
        return $this->render(
            'Default/online.html.twig',
            [
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy([], ['datetime' => 'DESC']),
            ]
        );
    }

    public function utilitiesAction(): Response
    {
        return $this->render('Default/utilities.html.twig');
    }

    public function programmingAction(): Response
    {
        return $this->render('Default/programming.html.twig');
    }

    public function openSourceAction(): Response
    {
        return $this->render('Default/open_source.html.twig');
    }

    public function gmanagerAction(): RedirectResponse
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    public function downloadsAction(): Response
    {
        return $this->render('Default/downloads.html.twig');
    }

    public function textbookAction(): Response
    {
        return $this->render('Default/textbook.html.twig');
    }

    public function videoCoursesAction(): Response
    {
        return $this->render('Default/video_courses.html.twig');
    }
}
