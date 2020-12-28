<?php

namespace App\Controller;

use App\Form\Type\Unicode\UnicodeType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/unicode")
 */
class UnicodeController extends AbstractController
{
    /**
     * @Route("", name="unicode_index")
     */
    public function indexAction(Request $request): Response
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

        return $this->render('Unicode/index.html.twig', [
            'form' => $form->createView(),
            'result' => $result,
        ]);
    }

    /**
     * @Route("/icons", name="unicode_icons")
     */
    public function iconsAction(): Response
    {
        return $this->render('Unicode/icons.html.twig');
    }

    protected function getUnicode(array $data): string
    {
        $out = $data['text'];
        if ($data['html']) {
            $out = $this->getHtmlSpecialChars($out);
        }

        if ($data['latin']) {
            $out = $this->getLatin($out);
        }

        $out = \json_encode($out, \JSON_THROW_ON_ERROR);

        $that = $this;
        $out = \preg_replace_callback('/\\\u([0-9a-z]{4})/', static function (array $matches) use ($that, $data) {
            $key = $matches[1];
            if ($data['zerofill']) {
                $key = $that->getZeroFill($key);
            }

            return '&#x'.$key.';';
        }, $out);

        $out = \mb_substr($out, 1); // удаляем вначале "
        $out = \mb_substr($out, 0, -1); // удаляем в конце "
        $out = \str_replace(['\"', '\\\\'], ['"', '\\'], $out);

        return $out;
    }

    protected function getLatin(string $str): string
    {
        return \strtr($str, [
            'А' => 'A', 'В' => 'B', 'Е' => 'E', 'К' => 'K', 'М' => 'M',
            'Н' => 'H', 'О' => 'O', 'Р' => 'Р', 'С' => 'C', 'Т' => 'T',
            'Х' => 'X', 'а' => 'a', 'е' => 'e', 'о' => 'o', 'р' => 'p',
            'с' => 'c', 'у' => 'y', 'х' => 'x',
        ]);
    }

    protected function getHtmlSpecialChars(string $str): string
    {
        return \htmlspecialchars($str, \ENT_QUOTES);
    }

    protected function getZeroFill(string $str): string
    {
        return \ltrim($str, '0');
    }
}
