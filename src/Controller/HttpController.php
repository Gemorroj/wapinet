<?php

namespace App\Controller;

use App\Form\Type\Http\HttpType;
use App\Helper\Curl;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function explode;
use function str_replace;
use function trim;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_POSTFIELDS;

class HttpController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(HttpType::class);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getHttp($data);
                }
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Http/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    private function getHttp(array $data): Response
    {
        /** @var Curl $curl */
        $curl = $this->get(Curl::class);

        $curl->init($data['url']);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, $data['type']);

        if (null !== $data['header']) {
            foreach (explode("\n", str_replace("\r", '', trim($data['header']))) as $header) {
                [$key, $value] = explode(':', $header, 2);
                $curl->addHeader($key, $value);
            }
        }

        if (null !== $data['body']) {
            $curl->setOpt(CURLOPT_POSTFIELDS, $data['body']);
        }

        return $curl->exec();
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Curl::class] = '?'.Curl::class;

        return $services;
    }
}
