<?php

namespace App\Controller;

use App\Form\Type\Http\HttpType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Route('/http')]
class HttpController extends AbstractController
{
    #[Route(path: '', name: 'http_index')]
    public function indexAction(Request $request, HttpClientInterface $httpClient): Response
    {
        $headers = null;
        $content = null;
        $form = $this->createForm(HttpType::class);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $formData = $form->getData();
                    $response = $this->getHttp($httpClient, $formData['url'], $formData['type'], $formData['header'], $formData['body']);

                    $content = $response->getContent(false); // first
                    $headers = $response->getInfo('response_headers');
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Http/index.html.twig', [
            'form' => $form->createView(),
            'headers' => $headers,
            'content' => $content,
        ]);
    }

    private function getHttp(HttpClientInterface $httpClient, string $url, string $method, ?string $headers, ?string $body): ResponseInterface
    {
        $options = [];

        if (null !== $headers) {
            foreach (\explode("\n", \str_replace("\r", '', \trim($headers))) as $header) {
                [$key, $value] = \explode(':', $header, 2);
                $options['headers'][$key] = $value;
            }
        }

        if (null !== $body) {
            $options['body'] = $body;
        }

        return $httpClient->request($method, $url, $options);
    }
}
