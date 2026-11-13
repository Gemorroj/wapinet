<?php

declare(strict_types=1);

namespace App\Service\Api\Validator;

use App\Dto\Api\Validator\ValidatorMessageResponse;
use App\Dto\Api\Validator\ValidatorRequest;
use App\Dto\Api\Validator\ValidatorResponse;
use App\Service\Api\CodeSourceLoader;
use App\Service\CssValidator;
use App\Service\HtmlValidator;
use App\Service\PhpValidator;
use CSSValidator\Options\ProfileType;
use CSSValidator\Options\WarningType;
use CSSValidator\Response as CssValidatorResponse;
use HTMLValidator\Message\Message as HtmlMessage;
use HTMLValidator\Response as HtmlValidatorResponse;

final readonly class ValidatorRunner implements ValidatorRunnerInterface
{
    public function __construct(
        private PhpValidator $phpValidator,
        private HtmlValidator $htmlValidator,
        private CssValidator $cssValidator,
        private CodeSourceLoader $sourceLoader,
    ) {
    }

    public function validatePhp(?string $code = null, ?string $url = null): ValidatorResponse
    {
        $code = $this->sourceLoader->load($code, $url, ValidatorRequest::MAX_CODE_SIZE);

        if (\str_starts_with($code, "\xEF\xBB\xBF")) {
            $code = \substr($code, 3);
        }

        if (!\str_starts_with(\ltrim($code), '<?')) {
            $code = '<?php '.$code;
        }

        return self::fromPhpResult($this->phpValidator->validateFragment($code), $code);
    }

    public function validateHtml(?string $code = null, ?string $url = null): ValidatorResponse
    {
        $code = $this->sourceLoader->load($code, $url, ValidatorRequest::MAX_CODE_SIZE);

        return self::fromHtmlResponse($this->htmlValidator->validateFragment($code), $code);
    }

    public function validateCss(
        ?string $code = null,
        ?string $url = null,
        ?ProfileType $profile = null,
        ?WarningType $warnings = null,
    ): ValidatorResponse {
        $options = $this->cssValidator->getOptions();
        $options->setProfile(($profile ?? ProfileType::Css3)->value);
        $options->setWarning(($warnings ?? WarningType::Normal)->value);
        $code = $this->sourceLoader->load($code, $url, ValidatorRequest::MAX_CODE_SIZE);

        return self::fromCssResponse($this->cssValidator->validateFragment($code), $code);
    }

    /**
     * @param array<string, mixed> $result
     */
    public static function fromPhpResult(array $result, ?string $source = null): ValidatorResponse
    {
        $errors = $result['errors'] ?? [];

        if (!\is_array($errors)) {
            $errors = [];
        }

        $messages = [];

        foreach ($errors as $error) {
            if (!\is_array($error)) {
                continue;
            }

            $messages[] = new ValidatorMessageResponse(
                'error',
                self::optionalString($error['type'] ?? null),
                self::optionalInt($error['line'] ?? null),
                null,
                (string) ($error['message'] ?? ''),
                null,
            );
        }

        return new ValidatorResponse((bool) ($result['validity'] ?? false), $messages, $source, null, null, null, \PHP_VERSION);
    }

    public static function fromHtmlResponse(HtmlValidatorResponse $response, ?string $source = null): ValidatorResponse
    {
        $messages = [];

        foreach ($response->getErrors() as $error) {
            $messages[] = self::fromHtmlMessage('error', $error);
        }

        foreach ($response->getWarnings() as $warning) {
            $messages[] = self::fromHtmlMessage('warning', $warning);
        }

        return new ValidatorResponse($response->isValid(), $messages, $source, $response->getType(), $response->getEncoding());
    }

    public static function fromCssResponse(CssValidatorResponse $response, ?string $source = null): ValidatorResponse
    {
        $messages = [];

        foreach ($response->getErrors() as $error) {
            $messages[] = new ValidatorMessageResponse(
                'error',
                '' === $error->getErrorType() ? null : $error->getErrorType(),
                $error->getLine(),
                null,
                (string) ($error->getMessage() ?? ''),
                null,
            );
        }

        foreach ($response->getWarnings() as $warning) {
            $messages[] = new ValidatorMessageResponse(
                'warning',
                self::optionalString($warning->getType()),
                $warning->getLine(),
                null,
                (string) ($warning->getMessage() ?? ''),
                null,
            );
        }

        return new ValidatorResponse($response->isValid(), $messages, $source, null, null, $response->getCssLevel());
    }

    private static function fromHtmlMessage(string $severity, HtmlMessage $message): ValidatorMessageResponse
    {
        return new ValidatorMessageResponse(
            $severity,
            null,
            $message->getFirstLine() ?? $message->getLastLine(),
            $message->getFirstColumn() ?? $message->getLastColumn(),
            (string) ($message->getMessage() ?? ''),
            $message->getExtract(),
        );
    }

    private static function optionalString(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }

    private static function optionalInt(mixed $value): ?int
    {
        return \is_int($value) ? $value : null;
    }
}
