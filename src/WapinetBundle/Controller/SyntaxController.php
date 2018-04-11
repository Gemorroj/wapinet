<?php

namespace WapinetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SyntaxController extends Controller
{
    /**
     * @var null|array
     */
    protected $data;


    /**
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $code = $request->get('f');

        if (null === $code) {
            return new Response('<div class="red">Не передан PHP код</div>');
        }

        $this->data = $this->get('php_validator')->validateFragment($code);
        $charset = $this->detectEncoding($code);

        $page = '<div class="border">Кодировка: ' . $charset . '<br/></div><div class="border">Размер: ' . $this->codeSize($code) . '<br/></div>';

        if ($this->data['validity']) {
            $page .= '<div class="green">Синтаксических ошибок не найдено<br/></div>';
        } else {
            $page .= '<div class="red">' . $this->data['errors'][0]['type'] . ': ' . $this->data['errors'][0]['message'] . '<br/>Ошибка в ' . $this->data['errors'][0]['line'] . ' строке<br/></div>';
        }

        $page .= $this->highlightCode($this->toUtf8Encoding($code, $charset), $this->data['errors'][0]['line']);

        return new Response($page);
    }


    /**
     * @param string $source
     *
     * @return string
     */
    protected function detectEncoding($source)
    {
        return \mb_detect_encoding($source, ['UTF-8', 'Windows-1251', 'KOI8-R', 'CP866', 'ISO-8859-1', 'US-ASCII'], true);
    }

    /**
     * @param string $source
     * @param string $encoding
     *
     * @return string
     */
    protected function toUtf8Encoding($source, $encoding)
    {
        if ('UTF-8' !== $encoding) {
            return \mb_convert_encoding($source, 'UTF-8', $encoding);
        }

        return $source;
    }

    /**
     * @param string $source
     *
     * @return string
     */
    protected function codeSize($source)
    {
        $size = \strlen($source);

        if ($size < 1024) {
            return $size . ' b';
        }

        return \round($size / 1024, 2) . ' kb';
    }

    /**
     * @param string $source
     * @param int    $line
     *
     * @return string
     */
    protected function highlightCode($source, $line = 0)
    {
        $array = \array_slice(\explode("\n", $this->xhtmlCode($source)), 1, -2);
        $all = \count($array);
        $len = \strlen($all);
        $page = '';
        for ($i = 0; $i < $all; ++$i) {
            $next = $i + 1;
            $l = \strlen($next);
            $page .= '<span class="' . ($line == $next ? 'fail_code' : 'true_code') . '">' . ($l < $len ? \str_repeat('&#160;', $len - $l) : '') . $next . '</span> ' . $array[$i] . "\n";
        }

        return '<div class="code"><pre><code>' . $page . '</code></pre></div>';
    }

    /**
     * @param string $source
     *
     * @return string
     */
    protected function xhtmlCode($source)
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
