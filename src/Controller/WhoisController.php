<?php

namespace App\Controller;

use App\Form\Type\Whois\WhoisType;
use App\Service\WhoRdap;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use WhoRdap\Response\RdapDomainRegistrarResponse;
use WhoRdap\Response\RdapDomainResponse;
use WhoRdap\Response\WhoisDomainRegistrarResponse;
use WhoRdap\Response\WhoisDomainResponse;

#[Route('/whois')]
class WhoisController extends AbstractController
{
    #[Route(path: '', name: 'whois_index')]
    public function indexAction(Request $request, WhoRdap $whoRdap): Response
    {
        $result = null;
        $type = 'WHOIS';
        $form = $this->createForm(WhoisType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $type = $data['type'];
                    $result = $this->getWhois($whoRdap, $data['query'], $data['type']);
                }
            } elseif (null !== $request->request->get('query')) {
                $type = $request->request->get('type', 'WHOIS');
                $form->setData([
                    'query' => $request->request->get('query'),
                    'type' => $type,
                ]);
                $data = $form->getData();

                $result = $this->getWhois($whoRdap, $data['query'], $data['type']);
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Whois/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
            'type' => $type,
        ]);
    }

    /**
     * @return array{server: string, response: string}
     */
    private function getWhois(WhoRdap $whoRdapService, string $query, string $type): array
    {
        $whoRdap = $whoRdapService->getWhoRdap();
        if ('RDAP' === $type) {
            $result = $whoRdap->processRdap($query);
            if ($result instanceof RdapDomainResponse && $result->registrarResponse instanceof RdapDomainRegistrarResponse) {
                return ['server' => $result->registrarResponse->server, 'response' => $result->registrarResponse->response];
            }

            return ['server' => $result->server, 'response' => $result->response];
        }

        $result = $whoRdap->processWhois($query);
        if ($result instanceof WhoisDomainResponse && $result->registrarResponse instanceof WhoisDomainRegistrarResponse) {
            return ['server' => $result->registrarResponse->server, 'response' => $result->registrarResponse->response];
        }

        return ['server' => $result->server, 'response' => $result->response];
    }
}
