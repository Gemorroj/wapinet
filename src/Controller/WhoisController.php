<?php

namespace App\Controller;

use App\Exception\WhoisException;
use App\Form\Type\Whois\WhoisType;
use App\Helper\Phpwhois;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WhoisController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        $resultHtml = null;
        $form = $this->createForm(WhoisType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $resultHtml = $this->getWhois($data);
                }
            } elseif (null !== $request->get('query')) {
                $data = ['query' => $request->get('query')];
                $form->setData($data);

                $resultHtml = $this->getWhois($data);
            }
        } catch (Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('Whois/index.html.twig', [
            'form' => $form->createView(),
            'resultHtml' => $resultHtml,
        ]);
    }

    /**
     * @param array $data
     *
     * @throws WhoisException
     *
     * @return string HTML текст
     */
    protected function getWhois(array $data): string
    {
        $phpwhois = $this->get(Phpwhois::class);
        $whois = $phpwhois->getWhois();

        //$whois->non_icann = true;
        $result = $whois->Lookup($data['query']);

        if (!empty($result['rawdata'])) {
            $result['rawdata'] = \str_replace('{query}', \htmlspecialchars($data['query']), $result['rawdata']);
            $utils = $phpwhois->getUtils();
            $resultHtml = $utils->showHTML($result);

            $resultHtml = \str_replace($_SERVER['PHP_SELF'], '', $resultHtml);

            $resultHtml = \str_replace('<a href=', '<a rel="external" href=', $resultHtml);

            return $resultHtml;
        }

        if (isset($whois->Query['errstr'])) {
            throw new WhoisException($whois->Query['errstr']);
        }

        throw new WhoisException(['Не найдено данных']);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[Phpwhois::class] = '?'.Phpwhois::class;

        return $services;
    }
}
