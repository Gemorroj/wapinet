<?php

namespace App\Controller;

use App\Form\Type\Whois\WhoisType;
use App\Service\WhoRdap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use WhoRdap\Response\DomainRegistrarResponse;
use WhoRdap\Response\DomainResponse;

#[Route('/whois')]
class WhoisController extends AbstractController
{
    #[Route(path: '', name: 'whois_index')]
    public function indexAction(Request $request, WhoRdap $whoRdap): Response
    {
        $result = null;
        $form = $this->createForm(WhoisType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getWhois($data, $whoRdap);
                }
            } elseif (null !== $request->get('query')) {
                $data = ['query' => $request->get('query')];
                $form->setData($data);

                $result = $this->getWhois($data, $whoRdap);
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Whois/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    /**
     * @throws \Exception
     *
     * @return string HTML текст
     */
    private function getWhois(array $data, WhoRdap $whoRdapService): string
    {
        $whoRdap = $whoRdapService->getWhoRdap();
        $result = $whoRdap->process($data['query']);

        if ($result instanceof DomainResponse && $result->registrarResponse instanceof DomainRegistrarResponse) {
            return $result->registrarResponse->getResponseAsString();
        }

        return $result->getResponseAsString();
    }
}
