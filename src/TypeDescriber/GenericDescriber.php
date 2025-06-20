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
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Annotations\Schema;
use phpDocumentor\Reflection\DocBlock\Tags\Template;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use Symfony\Component\PropertyInfo\Type as LegacyType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\GenericType;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * @implements TypeDescriberInterface<GenericType>
 *
 * @internal
 * todo think about better name? GenericClassDescriber?
 */
final class GenericDescriber implements TypeDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;
    public const TEMPLATES_KEY = 'templateTypes';

    private DocBlockFactoryInterface $docBlockFactory;

    public function __construct()
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function describe(Type $type, Schema $schema, array $context = []): void
    {
        $wrappedType = $type->getWrappedType();

        // todo handle nullable?

        try {
            /** @var Template[] $templateTags */
            $templateTags = $this->docBlockFactory
                ->create(new \ReflectionClass($wrappedType->getClassName()))
                ->getTagsByName('template');
            $templateNames = array_map(
                static fn (Template $template): string => $template->getTemplateName(),
                $templateTags,
            );

            // todo check whether $context is global var or not (we want to only narrow down types from here)
            $context[self::TEMPLATES_KEY] = array_combine($templateNames, $type->getVariableTypes());
        } catch (\Throwable $e) {
            // todo check exceptions here
            return;
        }

        // todo handle multiple same wrapped classes with different template types (in API schema)
        $schema->ref = $this->modelRegistry->register(
            new Model(new LegacyType('object', false, $wrappedType->getClassName()), serializationContext: $context)
        );
    }

    public function supports(Type $type, array $context = []): bool
    {
        return $type instanceof GenericType
            && $type->getWrappedType() instanceof ObjectType;
    }
}
