<?php

namespace App\Service;

use CSSValidator\CSSValidator as W3CService;
use CSSValidator\Exception as W3CServiceException;
use CSSValidator\Options as W3CServiceOptions;
use CSSValidator\Response as W3CServiceResponse;
use Symfony\Component\HttpFoundation\File\File;

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

    public function getOptions(): W3CServiceOptions
    {
        return $this->cssValidator->getOptions();
    }

    /**
     * @throws W3CServiceException
     */
    public function validateFragment(string $css): W3CServiceResponse
    {
        return $this->cssValidator->validateFragment($css);
    }

    /**
     * @throws W3CServiceException
     */
    public function validateFile(File $file): W3CServiceResponse
    {
        return $this->cssValidator->validateFile($file->getPathname());
    }
}
