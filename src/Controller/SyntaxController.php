<?php

namespace App\Controller;

use App\Service\PhpValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SyntaxController extends AbstractController
{
    /**
     * Check syntax for Gmanager.
     */
    #[Route(path: '/syntax', name: 'syntax')]
    #[Route(path: '/syntax2/index.php', name: 'syntax_old')]
    public function indexAction(Request $request, PhpValidator $phpValidator): Response
    {
        $code = $request->get('f');

        if (null === $code) {
            return new Response('<div class="red">Не передан PHP код</div>');
        }

        $data = $phpValidator->validateFragment($code);
        $charset = $this->detectEncoding($code);

        $page = '<div class="border">Кодировка: '.$charset.'<br/></div><div class="border">Размер: '.$this->codeSize($code).'<br/></div>';

        if ($data['validity']) {
            $page .= '<div class="green">Синтаксических ошибок не найдено<br/></div>';
        } else {
            $page .= '<div class="red">'.$data['errors'][0]['type'].': '.$data['errors'][0]['message'].'<br/>Ошибка в '.$data['errors'][0]['line'].' строке<br/></div>';
        }

        $page .= $this->highlightCode($this->toUtf8Encoding($code, $charset), $data['errors'][0]['line'] ?? null);

        return new Response($page);
    }

    private function detectEncoding(string $source): string
    {
        return \mb_detect_encoding($source, ['UTF-8', 'Windows-1251', 'KOI8-R', 'CP866', 'ISO-8859-1', 'US-ASCII'], true);
    }

    private function toUtf8Encoding(string $source, string $encoding): string
    {
        if ('UTF-8' !== $encoding) {
            return \mb_convert_encoding($source, 'UTF-8', $encoding);
        }

        return $source;
    }

    private function codeSize(string $source): string
    {
        $size = \strlen($source);

        if ($size < 1024) {
            return $size.' b';
        }

        return \round($size / 1024, 2).' kb';
    }

    private function highlightCode(string $source, ?int $line = null): string
    {
        $array = \array_slice(\explode("\n", $this->xhtmlCode($source)), 1, -2);
        $all = \count($array);
        $len = \mb_strlen((string) $all);
        $page = '';
        for ($i = 0; $i < $all; ++$i) {
            $next = $i + 1;
            $l = \mb_strlen((string) $next);
            $page .= '<span class="'.($line === $next ? 'fail_code' : 'true_code').'">'.($l < $len ? \str_repeat('&#160;', $len - $l) : '').$next.'</span> '.$array[$i]."\n";
        }

        return '<div class="code"><pre><code>'.$page.'</code></pre></div>';
    }

    private function xhtmlCode(string $source): string
    {
        return \str_replace(
            ['&nbsp;', '<code>', '</code>', '<br />'],
            [' ', '', '', "\n"],
            \preg_replace(
                '#color="(.*?)"#',
                'style="color: $1"',
                \str_replace(
                    ['<font ', '</font>'],
                    ['<span ', '</span>'],
                    \highlight_string($source, true)
                )
            )
        );
    }
}
