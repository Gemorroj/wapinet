<?php

namespace App\Helper;

use HTMLValidator\Exception as W3CServiceException;
use HTMLValidator\HTMLValidator as W3CService;
use HTMLValidator\Response as W3CServiceResponse;
use Symfony\Component\HttpFoundation\File\File;

/**
 * HtmlValidator хэлпер
 */
class HtmlValidator
{
    /**
     * @var W3CService
     */
    protected $htmlValidator;

    /**
     * Конструктор
     */
    public function __construct()
    {
        $this->htmlValidator = new W3CService();
    }

    /**
     * @param string $html
     *
     * @throws W3CServiceException
     *
     * @return W3CServiceResponse
     */
    public function validateFragment(string $html): W3CServiceResponse
    {
        return $this->htmlValidator->validateFragment($html);
    }

    /**
     * @param File $file
     *
     * @throws W3CServiceException
     *
     * @return W3CServiceResponse
     */
    public function validateFile(File $file): W3CServiceResponse
    {
        return $this->htmlValidator->validateFile($file->getPathname());
    }
}
