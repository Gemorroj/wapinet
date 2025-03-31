<?php

namespace App\Controller;

use App\Exception\WhoisException;
use App\Form\Type\Whois\WhoisType;
use App\Service\Phpwhois;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/whois')]
class WhoisController extends AbstractController
{
    #[Route(path: '', name: 'whois_index')]
    public function indexAction(Request $request, Phpwhois $phpwhois): Response
    {
        $resultHtml = null;
        $form = $this->createForm(WhoisType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $resultHtml = $this->getWhois($data, $phpwhois);
                }
            } elseif (null !== $request->get('query')) {
                $data = ['query' => $request->get('query')];
                $form->setData($data);

                $resultHtml = $this->getWhois($data, $phpwhois);
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Whois/index.html.twig', [
            'form' => $form->createView(),
            'resultHtml' => $resultHtml,
        ]);
    }

    /**
     * @throws WhoisException
     *
     * @return string HTML текст
     */
    private function getWhois(array $data, Phpwhois $phpwhois): string
    {
        $whois = $phpwhois->getWhois();

        $result = $whois->lookup($data['query']);

        if ($result->rawData) {
            $result->rawData = \str_replace('{query}', \htmlspecialchars($data['query']), $result->rawData);
            $link = $this->generateUrl('whois_index');

            return $whois::showHTML($result, $link);
        }

        if ($result->errstr) {
            throw new WhoisException($result->errstr);
        }

        throw new WhoisException(['Не найдено данных']);
    }
}
