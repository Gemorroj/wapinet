<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\ProcessBuilder;
use Wapinet\Bundle\Entity\Sea;

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
        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix(
            $this->container->getParameter('wapinet_hg_path')
        );
        $processBuilder->setArguments(array(
            'log',
            '-l ' . $this->container->getParameter('wapinet_hg_log')
        ));
        $processBuilder->setEnv('LC_ALL', 'ru_RU.utf8');
        $output = $processBuilder->getProcess()->mustRun()->getOutput();
        //$output = iconv('cp1251', 'utf-8', $output); // windows only

        return $this->render('WapinetBundle:Default:log.html.twig', array('log' => $this->formatLog($output)));
    }

    /**
     * Преобразовываем строковый вывод консоли в массив
     *
     * @param string $output
     * @return array
     */
    private function formatLog($output)
    {
        $result = array();
        $output = rtrim($output);
        $changes = explode("\n\n", $output);
        $timezone = $this->get('timezone')->getTimezone();

        foreach ($changes as $change) {
            $items = explode("\n", $change);

            $tmpResult = array();
            foreach ($items as $item) {
                list($key, $value) = explode(':', $item, 2);

                if ('date' === $key) {
                    $tmpResult[$key] = new \DateTime(ltrim($value), $timezone);
                } else {
                    $tmpResult[$key] = ltrim($value);
                }
            }

            $result[] = $tmpResult;
        }

        return $result;
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

        $file = $this->container->getParameter('wapinet_sea_file');

        $response = new BinaryFileResponse($file);
        $response->setPrivate();
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'sea_for_customers.zip'
        );

        return $response;
    }
}
