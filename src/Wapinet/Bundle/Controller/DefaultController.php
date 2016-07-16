<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Wapinet\Bundle\Entity\Sea;
use GitElephant\Repository;
use GitElephant\GitBinary;

class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('WapinetBundle:Default:index.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutAction()
    {
        return $this->render('WapinetBundle:Default:about.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logAction()
    {
        $git = new GitBinary($this->getParameter('wapinet_git_path'));

        $repo = new Repository(__DIR__, $git, 'wapinet');

        $log = $repo->getLog(
            'HEAD',
            null,
            $this->getParameter('wapinet_git_log')
        );

        return $this->render('WapinetBundle:Default:log.html.twig', array('log' => $log));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onlineAction()
    {
        return $this->render(
            'WapinetBundle:Default:online.html.twig',
            array(
                'online' => $this->getDoctrine()->getRepository('WapinetBundle:Online')->findBy(array(), array('datetime' => 'DESC'))
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function utilitiesAction()
    {
        return $this->render('WapinetBundle:Default:utilities.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function programmingAction()
    {
        return $this->render('WapinetBundle:Default:programming.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function openSourceAction()
    {
        return $this->render('WapinetBundle:Default:open_source.html.twig');
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
        return $this->render('WapinetBundle:Default:downloads.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function buyPhpScriptsAction()
    {
        return $this->render('WapinetBundle:Default:buy_php_scripts.html.twig');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function textbookAction()
    {
        return $this->render('WapinetBundle:Default:textbook.html.twig');
    }

    /**
     * @param Request $request
     * @return BinaryFileResponse
     */
    public function seaDownloadAction(Request $request)
    {
        $seaCustomer = new Sea();
        $seaCustomer->setDatetime(new \DateTime());
        $seaCustomer->setIp($request->getClientIp());
        $seaCustomer->setBrowser($request->headers->get('User-Agent', ''));
        $seaCustomer->setReferer($request->headers->get('Referer'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($seaCustomer);
        $em->flush();

        $file = $this->getParameter('wapinet_sea_file');

        $response = new BinaryFileResponse($file);
        $response->setPrivate();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'sea_for_customers.zip'
        );

        return $response;
    }
}
