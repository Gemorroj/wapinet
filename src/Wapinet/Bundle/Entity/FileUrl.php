<?php

namespace Wapinet\Bundle\Entity;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

/**
 * File or url
 */
class FileUrl extends File
{
    /**
     * The original name of the uploaded file (basename).
     *
     * @var string
     */
    protected $originalName;

    /**
     * The mime type provided by the uploader (Content-Type).
     *
     * @var string
     */
    protected $mimeType;

    /**
     * The file size provided by the uploader (Content-Length).
     *
     * @var string
     */
    private $size;

    public function __construct($file, $url, $mimeType = null, $size = null)
    {
        parent::__construct($file);

        $this->originalName = $this->getName($url);
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->size = $size;
    }


    /**
     * Returns the file size.
     *
     * It is extracted from the request from which the file has been uploaded.
     * Then is should not be considered as a safe value.
     *
     * @return integer|null The file size
     *
     * @api
     */
    public function getClientSize()
    {
        return $this->size;
    }


    /**
     * Returns the file mime type.
     *
     * The client mime type is extracted from the request from which the file
     * was uploaded, so it should not be considered as a safe value.
     *
     * For a trusted mime type, use getMimeType() instead (which guesses the mime
     * type based on the file content).
     *
     * @return string|null The mime type
     *
     * @see getMimeType
     *
     * @api
     */
    public function getClientMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Returns the extension based on the client mime type.
     *
     * If the mime type is unknown, returns null.
     *
     * This method uses the mime type as guessed by getClientMimeType()
     * to guess the file extension. As such, the extension returned
     * by this method cannot be trusted.
     *
     * For a trusted extension, use guessExtension() instead (which guesses
     * the extension based on the guessed mime type for the file).
     *
     * @return string|null The guessed extension or null if it cannot be guessed
     *
     * @see guessExtension()
     * @see getClientMimeType()
     */
    public function guessClientExtension()
    {
        $type = $this->getClientMimeType();
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($type);
    }

    /**
     * Returns the original file name.
     *
     * It is extracted from the request from which the file has been uploaded.
     * Then is should not be considered as a safe value.
     *
     * @return string|null The original name
     *
     * @api
     */
    public function getClientOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Returns the original file extension
     *
     * It is extracted from the original file name that was uploaded.
     * Then is should not be considered as a safe value.
     *
     * @return string The extension
     */
    public function getClientOriginalExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }
}
