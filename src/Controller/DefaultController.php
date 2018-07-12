<?php

namespace App\Controller;

use App\Entity\Online;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/index.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/about.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onlineAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render(
            'Default/online.html.twig',
            [
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy([], ['datetime' => 'DESC'])
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function utilitiesAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/utilities.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function programmingAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/programming.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function openSourceAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/open_source.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function gmanagerAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadsAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/downloads.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function textbookAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/textbook.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function videoCoursesAction(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Default/video_courses.html.twig');
    }
}
