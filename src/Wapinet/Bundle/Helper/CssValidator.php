<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\HttpFoundation\File\File;

/**
 * CssValidator хэлпер
 */
class CssValidator
{
    /**
     * @var \Services_W3C_CSSValidator
     */
    protected $cssValidator;

    public function __construct()
    {
        $this->cssValidator = new \Services_W3C_CSSValidator;
        $this->cssValidator->lang = 'ru';
    }

    /**
     * @param string $warning
     * @return CssValidator
     */
    public function setWarning($warning)
    {
        $this->cssValidator->warning = $warning;
        return $this;
    }

    /**
     * @param string $profile
     * @return CssValidator
     */
    public function setProfile($profile)
    {
        $this->cssValidator->profile = $profile;
        return $this;
    }

    /**
     * @param string $usermedium
     * @return CssValidator
     */
    public function setUsermedium($usermedium)
    {
        $this->cssValidator->usermedium = $usermedium;
        return $this;
    }

    /**
     * @param string $css
     * @throws \RuntimeException
     * @return \Services_W3C_CSSValidator_Response
     */
    public function validateFragment($css)
    {
        $result = $this->cssValidator->validateFragment($css);
        if (false === $result) {
            throw new \RuntimeException('Ошибка при проверке CSS');
        }
        return $result;
    }

    /**
     * @param File $file
     * @throws \RuntimeException
     * @return \Services_W3C_CSSValidator_Response
     */
    public function validateFile(File $file)
    {
        $result = $this->cssValidator->validateFile($file->getPathname());
        if (false === $result) {
            throw new \RuntimeException('Ошибка при проверке CSS');
        }
        return $result;
    }
}
