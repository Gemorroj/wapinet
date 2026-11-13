<?php

declare(strict_types=1);

namespace App\ApiDoc;

use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteArgumentDescriber\SymfonyMapQueryStringDescriber;
use OpenApi\Analysis;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Marks nested MapQueryString objects (for example `filter[...]`) as OpenAPI deepObject parameters.
 *
 * @see \Nelmio\ApiDocBundle\Processor\MapQueryStringProcessor
 */
final readonly class MapQueryStringDeepObjectProcessor
{
    public function __invoke(Analysis $analysis): void
    {
        foreach ($analysis->getAnnotationsOfType(OA\Operation::class) as $operation) {
            $contexts = $operation->_context->{SymfonyMapQueryStringDescriber::CONTEXT_KEY} ?? null;

            if (!\is_array($contexts)) {
                continue;
            }

            foreach ($contexts as $context) {
                if (!\is_array($context)) {
                    continue;
                }

                $metadata = $context[SymfonyMapQueryStringDescriber::CONTEXT_ARGUMENT_METADATA] ?? null;

                if (!$metadata instanceof ArgumentMetadata) {
                    continue;
                }

                foreach ($this->nestedParameterNames($metadata) as $name) {
                    $parameter = Util::getOperationParameter($operation, $name, 'query');
                    $parameter->style = 'deepObject';
                    $parameter->explode = true;
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function nestedParameterNames(ArgumentMetadata $metadata): array
    {
        $type = $metadata->getType();

        if (null === $type || !\class_exists($type)) {
            return [];
        }

        $constructor = new \ReflectionClass($type)->getConstructor();

        if (null === $constructor) {
            return [];
        }

        $names = [];

        foreach ($constructor->getParameters() as $parameter) {
            $parameterType = $parameter->getType();

            if ($parameterType instanceof \ReflectionNamedType && !$parameterType->isBuiltin()) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }
}
