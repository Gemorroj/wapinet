<?php

namespace App\Service;

use HTMLValidator\Exception as W3CServiceException;
use HTMLValidator\HTMLValidator as W3CService;
use HTMLValidator\Response as W3CServiceResponse;
use Symfony\Component\HttpFoundation\File\File;

final readonly class HtmlValidator
{
    private W3CService $htmlValidator;

    public function __construct()
    {
        $this->htmlValidator = new W3CService();
    }

    /**
     * @throws W3CServiceException
     */
    public function validateFragment(string $html): W3CServiceResponse
    {
        return $this->htmlValidator->validateFragment($html);
    }

    /**
     * @throws W3CServiceException
     */
    public function validateFile(File $file): W3CServiceResponse
    {
        return $this->htmlValidator->validateFile($file->getPathname());
    }
}
