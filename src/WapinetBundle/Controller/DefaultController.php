<?php

namespace WapinetBundle\Controller;

use GitElephant\GitBinary;
use GitElephant\Repository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use WapinetBundle\Entity\Online;

class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('@Wapinet/Default/index.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutAction()
    {
        return $this->render('@Wapinet/Default/about.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GitElephant\Exception\InvalidRepositoryPathException
     */
    public function logAction()
    {
        $git = new GitBinary($this->getParameter('wapinet_git_path'));

        $repo = new Repository($this->container->getParameter('kernel.root_dir') . '/..', $git, 'wapinet');

        $log = $repo->getLog(
            'HEAD',
            null,
            $this->getParameter('wapinet_git_log')
        );

        return $this->render('@Wapinet/Default/log.html.twig', array('log' => $log));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onlineAction()
    {
        return $this->render(
            '@Wapinet/Default/online.html.twig',
            array(
                'online' => $this->getDoctrine()->getRepository(Online::class)->findBy(array(), array('datetime' => 'DESC'))
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function utilitiesAction()
    {
        return $this->render('@Wapinet/Default/utilities.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function programmingAction()
    {
        return $this->render('@Wapinet/Default/programming.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function openSourceAction()
    {
        return $this->render('@Wapinet/Default/open_source.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function gmanagerAction()
    {
        return $this->redirect('https://github.com/Gemorroj/gmanager', 301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadsAction()
    {
        return $this->render('@Wapinet/Default/downloads.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function textbookAction()
    {
        return $this->render('@Wapinet/Default/textbook.html.twig');
    }
}
