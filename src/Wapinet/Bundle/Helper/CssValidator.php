<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\HttpFoundation\File\File;
use CSSValidator\CSSValidator as W3CService;
use CSSValidator\Options as W3CServiceOptions;
use CSSValidator\Response as W3CServiceResponse;
use CSSValidator\Exception as W3CServiceException;

/**
 * CssValidator хэлпер
 */
class CssValidator
{
    /**
     * @var W3CService
     */
    protected $cssValidator;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->cssValidator = new W3CService();
        $this->getOptions()->setLang('ru');
    }

    /**
     * @return W3CServiceOptions
     */
    public function getOptions()
    {
        return $this->cssValidator->getOptions();
    }

    /**
     * @param string $css
     * @throws W3CServiceException
     * @return W3CServiceResponse
     */
    public function validateFragment($css)
    {
        return $this->cssValidator->validateFragment($css);
    }

    /**
     * @param File $file
     * @throws W3CServiceException
     * @return W3CServiceResponse
     */
    public function validateFile(File $file)
    {
        return $this->cssValidator->validateFile($file->getPathname());
    }
}
