<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Exception\WhoisException;
use Wapinet\Bundle\Form\Type\Whois\WhoisType;

class WhoisController extends Controller
{
    public function indexAction(Request $request)
    {
        $resultHtml = null;
        $form = $this->createForm(new WhoisType());
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = $form->getData();

                $resultHtml = $this->lookup($form, $data);
            }
        } elseif (null !== $request->get('query')) {
            $data = array('query' => $request->get('query'));
            $form->setData($data);

            $resultHtml = $this->lookup($form, $data);
        }

        return $this->render('WapinetBundle:Whois:index.html.twig', array(
            'form' => $form->createView(),
            'resultHtml' => $resultHtml,
        ));
    }

    /**
     * @param Form $form
     * @param array $data
     * @return string|null
     */
    protected function lookup(Form $form, array $data)
    {
        try {
            return $this->getWhois($data);
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }
        return null;
    }

    /**
     * @param array $data
     * @return string
     * @throws WhoisException
     */
    protected function getWhois(array $data)
    {
        $phpwhois = $this->get('phpwhois');
        $whois = $phpwhois->getWhois();

        //$whois->non_icann = true;
        $result = $whois->Lookup($data['query']);

        if (!empty($result['rawdata'])) {
            $result['rawdata'] = str_replace('{query}', htmlspecialchars($data['query']), $result['rawdata']);
            $utils = $phpwhois->getUtils();
            $resultHtml = $utils->showHTML($result);

            $resultHtml = str_replace($_SERVER['PHP_SELF'], '', $resultHtml);
            return $resultHtml;
        }

        if (isset($whois->Query['errstr'])) {
            throw new WhoisException($whois->Query['errstr']);
        } else {
            throw new WhoisException(array('Неизвестная ошибка'));
        }
    }

}
