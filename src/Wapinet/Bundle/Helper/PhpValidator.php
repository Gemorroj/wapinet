<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * PhpValidator хэлпер
 */
class PhpValidator
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param File $file
     * @return array
     * @throws \RuntimeException
     */
    public function validateFile(File $file)
    {
        $php = $this->container->getParameter('wapinet_php_path');
        $cliArray = array();
        $code = 0;

        exec(escapeshellcmd($php) . ' -c -f -l ' . escapeshellarg($file->getPathname()), $cliArray, $code);

        if (0 === $code) {
            return array('validity' => true, 'errors' => null);
        }

        $size = sizeof($cliArray);
        if ($size > 2) {
            $message = preg_replace('/ in (?:.+) on line (?:[0-9]+)$/', '', $cliArray[1]);
            preg_match('/ on line ([0-9]+)$/', $cliArray[1], $matchLine);
            $line = $matchLine[1];

            list($type, $message) = explode(':', $message);

            return array(
                'validity' => false,
                'errors' => array(
                    array(
                        'code' => $code,
                        'line' => $line,
                        'type' => $type,
                        'message' => $message
                    ),
                ),
            );
        }

        throw new \RuntimeException('Ошибка при проверке PHP');
    }


    /**
     * @param string $source
     * @return array
     * @throws \RuntimeException
     */
    public function validateFragment($source)
    {
        $tmp = tempnam($this->container->get('kernel')->getTmpDir(), 'php_validator');
        if (false === $tmp) {
            throw new \RuntimeException('Не удалось создать временный файл');
        }
        if (false === file_put_contents($tmp, $source)) {
            throw new \RuntimeException('Не удалось записать данные во временный файл');
        }
        if (false === chmod($tmp, 0644)) {
            throw new \RuntimeException('Не удалось изменить права доступа временному файлу');
        }

        return $this->validateFile(new File($tmp, false));
    }
}
