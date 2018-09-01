<?php

namespace App\Controller;

use App\Entity\Online;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        return $this->render('Default/index.html.twig');
    }

    /**
     * @return Response
     */
    public function aboutAction(): Response
    {
        return $this->render('Default/about.html.twig');
    }

    /**
     * @return Response
     */
    public function onlineAction(): Response
    {
        return $this->render(
            'Default/online.html.twig',
            [
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy([], ['datetime' => 'DESC']),
            ]
        );
    }

    /**
     * @return Response
     */
    public function utilitiesAction(): Response
    {
        return $this->render('Default/utilities.html.twig');
    }

    /**
     * @return Response
     */
    public function programmingAction(): Response
    {
        return $this->render('Default/programming.html.twig');
    }

    /**
     * @return Response
     */
    public function openSourceAction(): Response
    {
        return $this->render('Default/open_source.html.twig');
    }

    /**
     * @return RedirectResponse
     */
    public function gmanagerAction(): RedirectResponse
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    /**
     * @return Response
     */
    public function downloadsAction(): Response
    {
        return $this->render('Default/downloads.html.twig');
    }

    /**
     * @return Response
     */
    public function textbookAction(): Response
    {
        return $this->render('Default/textbook.html.twig');
    }

    /**
     * @return Response
     */
    public function videoCoursesAction(): Response
    {
        return $this->render('Default/video_courses.html.twig');
    }
}
