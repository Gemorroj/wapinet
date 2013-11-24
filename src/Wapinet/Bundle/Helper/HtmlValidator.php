<?php
namespace Wapinet\Bundle\Helper;

/**
 * HtmlValidator хэлпер
 */
class HtmlValidator
{
    /**
     * @var \Services_W3C_HTMLValidator
     */
    protected $htmlValidator;

    public function __construct()
    {
        $this->htmlValidator = new \Services_W3C_HTMLValidator;
    }

    /**
     * @param string $html
     * @return \Services_W3C_HTMLValidator
     * @throws \RuntimeException
     */
    public function validateFragment($html)
    {
        $result = $this->htmlValidator->validateFragment($html);
        if (false === $result) {
            throw new \RuntimeException('Ошибка при проверке HTML');
        }
        return $result;
    }
}
