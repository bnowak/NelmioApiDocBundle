<?php

declare(strict_types=1);

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\TypeDescriber;

use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use OpenApi\Annotations\Schema;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\TemplateType;

/**
 * @implements TypeDescriberInterface<TemplateType>
 *
 * @internal
 */
final class TemplateDescriber implements TypeDescriberInterface, TypeDescriberAwareInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;
    use TypeDescriberAwareTrait;

    public function describe(Type $type, Schema $schema, array $context = []): void
    {
        $templateTypes = $context[GenericClassDescriber::TEMPLATES_KEY];

        if (\array_key_exists($type->getName(), $templateTypes)) {
            $resolvedType = $templateTypes[$type->getName()];

            if ($this->describer->supports($resolvedType, $context)) {
                $this->describer->describe($resolvedType, $schema, $context);
            }
        }
    }

    public function supports(Type $type, array $context = []): bool
    {
        return $type instanceof TemplateType
            && \array_key_exists(GenericClassDescriber::TEMPLATES_KEY, $context);
    }
}
