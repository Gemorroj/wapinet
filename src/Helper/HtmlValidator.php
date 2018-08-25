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
     * @return W3CServiceResponse
     *
     * @throws W3CServiceException
     */
    public function validateFragment($html)
    {
        return $this->htmlValidator->validateFragment($html);
    }

    /**
     * @param File $file
     *
     * @return W3CServiceResponse
     *
     * @throws W3CServiceException
     */
    public function validateFile(File $file)
    {
        return $this->htmlValidator->validateFile($file->getPathname());
    }
}
