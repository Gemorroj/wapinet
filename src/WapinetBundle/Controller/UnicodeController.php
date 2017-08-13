<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use WapinetBundle\Form\Type\Unicode\UnicodeType;
use Symfony\Component\Form\FormError;


class UnicodeController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(UnicodeType::class);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getUnicode($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:Unicode:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function iconsAction()
    {
        return $this->render('WapinetBundle:Unicode:icons.html.twig');
    }


    /**
     * @param array $data
     *
     * @return string
     */
    protected function getUnicode(array $data)
    {
        $out = $data['text'];
        if ($data['html']) {
            $out = $this->getHtmlSpecialChars($out);
        }

        if ($data['latin']) {
            $out = $this->getLatin($out);
        }

        $out = \json_encode($out);

        $that = $this;
        $out = \preg_replace_callback('/\\\u([0-9a-z]{4})/', function (array $matches) use ($that, $data) {
            $key = $matches[1];
            if ($data['zerofill']) {
                $key = $that->getZeroFill($key);
            }
            return '&#x' . $key . ';';
        }, $out);


        $out = \substr($out, 1); // удаляем вначале "
        $out = \substr($out, 0, -1); // удаляем в конце "
        $out = \str_replace(array('\"', '\\\\'), array('"', '\\'), $out);

        return $out;
    }


    /**
     * @param string $str
     * @return string
     */
    protected function getLatin($str)
    {
        return \strtr($str, array(
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M',
            'Н' => 'H', 'О' => 'O', 'Р' => 'Р', 'С' => 'C', 'Т' => 'T',
            'Х' => 'X', 'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p',
            'с' => 'c', 'у' => 'y', 'х' => 'x'
        ));
    }

    /**
     * @param string $str
     * @return string
     */
    protected function getHtmlSpecialChars($str)
    {
        return \htmlspecialchars($str, \ENT_QUOTES);
    }

    /**
     * @param string $str
     * @return string
     */
    protected function getZeroFill($str)
    {
        return \ltrim($str, 0);
    }
}
