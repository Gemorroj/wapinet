<?php

namespace App\Controller;

use App\Form\Type\Obfuscator\ObfuscatorType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/php_obfuscator")
 */
class PhpObfuscatorController extends AbstractController
{
    /**
     * @Route("", name="php_obfuscator_index")
     */
    public function indexAction(Request $request): Response
    {
        $result = null;
        $form = $this->createForm(ObfuscatorType::class);

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

        return $this->render('PhpObfuscator/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    /**
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

        $code = 'eval(urldecode(base64_decode(\''.\base64_encode(\urlencode($code)).'\')));';
        $code = 'eval(rawurldecode(base64_decode(\''.\base64_encode(\rawurlencode($code)).'\')));';

        for ($i = 0, $l = \mt_rand(1, 3); $i <= $l; ++$i) {
            $code = 'eval(gzinflate(base64_decode(\''.\base64_encode(\gzdeflate($code, 6)).'\')));';
        }

        return '<?php eval(gzinflate(base64_decode(\''.\base64_encode(\gzdeflate($code, 9)).'\')));';
    }

    /**
     * Удаляем начальные и завершающие php теги.
     *
     * @param string $code
     *
     * @return string
     */
    protected function cleanCode($code)
    {
        // Верх
        if (0 === \stripos($code, '<?php')) {
            $code = \mb_substr($code, 5, \mb_strlen($code));
        } elseif (0 === \strpos($code, '<?')) {
            $code = \mb_substr($code, 2, \mb_strlen($code));
        }
        // Низ
        if ('?>' === \mb_substr($code, -2, 2)) {
            $code = \mb_substr($code, 0, -2);
        }

        return $code;
    }
}
