<?php

namespace Wapinet\Bundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Wapinet\Bundle\Form\Type\Obfuscator\ObfuscatorType;

class PhpObfuscatorController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $result = null;
        $form = $this->createForm(new ObfuscatorType());

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                if ($form->isValid()) {
                    $data = $form->getData();
                    $result = $this->getObfuscator($data);
                }
            }
        } catch (\Exception $e) {
            $form->addError(new FormError($e->getMessage()));
        }

        return $this->render('WapinetBundle:PhpObfuscator:index.html.twig', array(
            'form' => $form->createView(),
            'result' => $result,
        ));
    }

    /**
     * @param array $data
     * @return string
     */
    protected function getObfuscator(array $data)
    {
        $code = $this->cleanCode($data['code']);

        if ($data['remove_comments']) {
            $code = \preg_replace("#(?<!:|\\\)//.*$#m", ' ', $code); // функция уничтожения комментариев
        }
        if ($data['remove_spaces']) {
            $code = \preg_replace("/([\n\t\r\f]+)/i", ' ', $code); // функция уничтожения новых строк, прогонов, возврата каретки и табуляции
            $code = \preg_replace("/([\s]{2,}+)/i", ' ', $code); // функция уничтожения лишних пробелов
        }

        $code = 'eval(urldecode(base64_decode(\'' . \base64_encode(\urlencode($code)) . '\')));';
        $code = 'eval(rawurldecode(base64_decode(\'' . \base64_encode(\rawurlencode($code)) . '\')));';

        for ($i = 0, $l = \mt_rand(1, 3); $i <= $l; ++$i) {
            $code = 'eval(gzinflate(base64_decode(\'' . \base64_encode(\gzdeflate($code, 6)) . '\')));';
        }

        return '<?php eval(gzinflate(base64_decode(\'' . \base64_encode(\gzdeflate($code, 9)) . '\')));';
    }

    /**
     * Удаляем начальные и завершающие php теги
     *
     * @param string $code
     * @return string
     */
    protected function cleanCode($code)
    {
        // Верх
        if (\mb_substr($code, 0, 5) == '<?php') {
            $code = \mb_substr($code, 5, \mb_strlen($code));
        } elseif (\mb_substr($code, 0, 2) == '<?') {
            $code = \mb_substr($code, 2, \mb_strlen($code));
        }
        // Низ
        if (\mb_substr($code, -2, 2) == '?>') {
            $code = \mb_substr($code, 0, \mb_strlen($code) - 2);
        }

        return $code;
    }
}
