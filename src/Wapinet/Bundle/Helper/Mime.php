<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mime хэлпер
 */
class Mime
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct (ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $url
     * @return array
     * @see http://www.php.net/manual/ru/function.mime-content-type.php#107798
     */
    private function generateMime ($url = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types')
    {
        $curl = $this->container->get('curl');
        $curl->init($url);
        $curl->addCompression();
        $response = $curl->exec();
        $curl->close();

        if (!$response->isSuccessful()) {
            throw new \RuntimeException('Не удалось получить данные (HTTP код: ' . $response->getStatusCode() . ')');
        }

        $mime = array(
            'mod' => 'audio/mod',
            'it' => 'audio/it',
            'amr' => 'audio/3gpp',
            'ini' => 'text/plain',
            'php' => 'text/html',
            'phtml' => 'text/html',
            'shtml' => 'text/html',
            'm4a' => 'audio/x-m4a',
        );
        foreach (\explode("\n", $response->getContent()) as $x) {
            if (isset($x[0]) && $x[0] !== '#' && \preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = \count($out[1])) > 1) {
                for ($i = 1; $i < $c; $i++) {
                    $mime[$out[1][$i]] = $out[1][0];
                }
            }
        }

        return $mime;
    }


    /**
     * @return array
     * @throws \RuntimeException
     */
    private function getMimeArray()
    {
        $cacheDir = $this->container->get('kernel')->getCacheDir();
        $mimeFileName = $cacheDir . \DIRECTORY_SEPARATOR . 'mime.types.php';

        if (true === \file_exists($mimeFileName)) {
            return include $mimeFileName;
        }

        $mimeArray = $this->generateMime();
        $result = \file_put_contents($mimeFileName, '<?php return ' . \var_export($mimeArray, true) . ';' . "\n");
        if (false === $result) {
            throw new \RuntimeException('Не удалось записать MIME типы');
        }

        return $mimeArray;
    }


    /**
     * @param string $path
     * @return string
     */
    public function getMimeType($path)
    {
        $extension = \strtolower(\pathinfo($path, PATHINFO_EXTENSION));
        $mimeArray = $this->getMimeArray();

        if (true === isset($mimeArray[$extension])) {
            return $mimeArray[$extension];
        }

        return 'application/octet-stream';
    }
}
