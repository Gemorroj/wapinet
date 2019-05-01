<?php

namespace App\Helper;

use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function count;
use function explode;
use function file_exists;
use function file_put_contents;
use function mb_strtolower;
use function pathinfo;
use function preg_match_all;
use function var_export;
use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

/**
 * Mime хэлпер
 */
class Mime
{
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    public function __construct(Curl $curl, ParameterBagInterface $parameterBag)
    {
        $this->curl = $curl;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param string $url
     *
     * @return array
     *
     * @see http://www.php.net/manual/ru/function.mime-content-type.php#107798
     */
    private function generateMime(string $url = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types'): array
    {
        $this->curl->init($url);
        $this->curl->addCompression();
        $response = $this->curl->exec();
        $this->curl->close();

        if (!$response->isSuccessful()) {
            throw new RuntimeException('Не удалось получить данные (HTTP код: '.$response->getStatusCode().')');
        }

        $mime = [
            'mod' => 'audio/mod',
            'it' => 'audio/it',
            'amr' => 'audio/3gpp',
            'ini' => 'text/plain',
            'php' => 'text/html',
            'phtml' => 'text/html',
            'shtml' => 'text/html',
            'm4a' => 'audio/x-m4a',
            '3gpp' => 'video/3gpp',
            'tgz' => 'application/gzip',
            'gz' => 'application/gzip',
        ];
        foreach (explode("\n", $response->getContent()) as $x) {
            if (isset($x[0]) && '#' !== $x[0] && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1) {
                for ($i = 1; $i < $c; ++$i) {
                    $mime[$out[1][$i]] = $out[1][0];
                }
            }
        }

        return $mime;
    }

    /**
     * @throws RuntimeException
     *
     * @return array
     */
    private function getMimeArray(): array
    {
        $cacheDir = $this->parameterBag->get('kernel.cache_dir');
        $mimeFileName = $cacheDir. DIRECTORY_SEPARATOR.'mime.types.php';

        if (true === file_exists($mimeFileName)) {
            return include $mimeFileName;
        }

        $mimeArray = $this->generateMime();
        $result = file_put_contents($mimeFileName, '<?php return '. var_export($mimeArray, true).';'."\n");
        if (false === $result) {
            throw new RuntimeException('Не удалось записать MIME типы');
        }

        return $mimeArray;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function getMimeType(string $path): string
    {
        $extension = mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeArray = $this->getMimeArray();

        if (true === isset($mimeArray[$extension])) {
            return $mimeArray[$extension];
        }

        return 'application/octet-stream';
    }
}
