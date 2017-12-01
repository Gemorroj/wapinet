<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use WapinetBundle\Exception\WhoisException;
use WapinetBundle\Form\Type\Whois\WhoisType;

class WhoisController extends Controller
{
    public function indexAction(Request $request)
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
                $data = array('query' => $request->get('query'));
                $form->setData($data);

                $resultHtml = $this->getWhois($data);
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Whois:index.html.twig', array(
            'form' => $form->createView(),
            'resultHtml' => $resultHtml,
        ));
    }

    /**
     * @param array $data
     * @return string HTML текст
     * @throws WhoisException
     */
    protected function getWhois(array $data)
    {
        $phpwhois = $this->get('phpwhois');
        $whois = $phpwhois->getWhois();

        //$whois->non_icann = true;
        $result = $whois->Lookup($data['query']);
        \dump($result);

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

        throw new WhoisException(array('Не найдено данных'));
    }

}
