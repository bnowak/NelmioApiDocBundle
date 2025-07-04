<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Functional;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Bazinga\Bundle\HateoasBundle\BazingaHateoasBundle;
use FOS\RestBundle\FOSRestBundle;
use JMS\SerializerBundle\JMSSerializerBundle;
use Nelmio\ApiDocBundle\NelmioApiDocBundle;
use Nelmio\ApiDocBundle\Render\Html\AssetsMode;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\BazingaUser;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\JMSComplex;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\NestedGroup\JMSPicture;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\PrivateProtectedExposure;
use Nelmio\ApiDocBundle\Tests\Functional\Entity\SymfonyConstraintsWithValidationGroups;
use Nelmio\ApiDocBundle\Tests\Functional\ModelDescriber\NameConverter;
use Nelmio\ApiDocBundle\Tests\Functional\ModelDescriber\VirtualTypeClassDoesNotExistsHandlerDefinedDescriber;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public const USE_JMS = 1;
    public const USE_BAZINGA = 2;
    public const USE_FOSREST = 3;
    public const USE_VALIDATION_GROUPS = 8;
    public const USE_FORM_CSRF = 16;
    public const USE_TYPE_INFO = 32;

    private int $flag;

    public function __construct(int $flag = 0)
    {
        parent::__construct('test'.$flag, true);

        $this->flag = $flag;
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new TwigBundle(),
            new ApiPlatformBundle(),
            new NelmioApiDocBundle(),
            new TestBundle(),
            new FOSRestBundle(),
        ];

        if (self::USE_JMS === $this->flag || self::USE_BAZINGA === $this->flag) {
            $bundles[] = new JMSSerializerBundle();

            if (self::USE_BAZINGA === $this->flag) {
                $bundles[] = new BazingaHateoasBundle();
            }
        }

        return $bundles;
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->withPath('/')->import(__DIR__.'/Resources/routes.yaml', 'yaml');

        if (self::USE_JMS === $this->flag || self::USE_BAZINGA === $this->flag) {
            $routes->withPath('/')->import(__DIR__.'/Controller/JMSController.php', 'attribute');
        }

        if (self::USE_BAZINGA === $this->flag) {
            $routes->withPath('/')->import(__DIR__.'/Controller/BazingaTypedController.php', 'attribute');
        }

        if (self::USE_FOSREST === $this->flag) {
            $routes->withPath('/')->import(__DIR__.'/Controller/FOSRestController.php', 'attribute');
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $framework = [
            'assets' => true,
            'secret' => 'MySecretKey',
            'test' => null,
            'validation' => null,
            'form' => null,
            'serializer' => [
                'enable_attributes' => true,
                'mapping' => [
                    'paths' => [__DIR__.'/Resources/serializer/'],
                ],
            ],
            'property_access' => true,
        ];

        if (self::USE_FORM_CSRF === $this->flag) {
            $framework['csrf_protection']['enabled'] = true;
            $framework['session']['storage_factory_id'] = 'session.storage.factory.mock_file';
            $framework['form'] = ['csrf_protection' => true];
        }

        $framework += [
            'exceptions' => [
                'Symfony\Component\HttpKernel\Exception\BadRequestHttpException' => [
                    'log_level' => 'debug',
                ],
            ],
        ];

        $c->loadFromExtension('framework', $framework);

        $c->loadFromExtension('twig', [
            'strict_variables' => '%kernel.debug%',
            'exception_controller' => null,
        ]);

        $c->loadFromExtension('api_platform', [
            'keep_legacy_inflector' => false,
            'mapping' => ['paths' => [
                '%kernel.project_dir%/tests/Functional/EntityExcluded/ApiPlatform3',
            ]],
        ]);

        $c->loadFromExtension('fos_rest', [
            'format_listener' => [
                'rules' => [
                    [
                        'path' => '^/',
                        'fallback_format' => 'json',
                    ],
                ],
            ],
        ]);

        // If FOSRestBundle 2.8
        if (class_exists(\FOS\RestBundle\EventListener\ResponseStatusCodeListener::class)) {
            $c->loadFromExtension('fos_rest', [
                'exception' => [
                    'enabled' => false,
                    'exception_listener' => false,
                    'serialize_exceptions' => false,
                ],
                'body_listener' => false,
                'routing_loader' => false,
            ]);
        }

        $models = [
            [
                'alias' => 'PrivateProtectedExposure',
                'type' => PrivateProtectedExposure::class,
            ],
            [
                'alias' => 'JMSPicture_mini',
                'type' => JMSPicture::class,
                'groups' => ['mini'],
            ],
            [
                'alias' => 'BazingaUser_grouped',
                'type' => BazingaUser::class,
                'groups' => ['foo'],
            ],
            [
                'alias' => 'SymfonyConstraintsTestGroup',
                'type' => SymfonyConstraintsWithValidationGroups::class,
                'groups' => ['test'],
            ],
            [
                'alias' => 'SymfonyConstraintsDefaultGroup',
                'type' => SymfonyConstraintsWithValidationGroups::class,
                'groups' => null,
            ],
        ];

        $models = array_merge($models, [
            [
                'alias' => 'JMSComplex',
                'type' => JMSComplex::class,
                'groups' => [
                    'list',
                    'details',
                    'User' => ['list'],
                ],
            ],
            [
                'alias' => 'JMSComplexDefault',
                'type' => JMSComplex::class,
                'groups' => null,
            ],
        ]);

        // Filter routes
        $c->loadFromExtension('nelmio_api_doc', [
            'type_info' => self::USE_TYPE_INFO === $this->flag,
            'html_config' => [
                'assets_mode' => AssetsMode::BUNDLE,
            ],
            'use_validation_groups' => self::USE_VALIDATION_GROUPS === $this->flag,
            'documentation' => [
                'info' => [
                    'title' => 'My Default App',
                    'x-buildHash' => 'ab1234567890',
                ],
                'paths' => [
                    // Ensures we can define routes in Yaml without defining OperationIds
                    // See https://github.com/zircote/swagger-php/issues/1153
                    '/api/test-from-yaml' => ['get' => [
                        'responses' => [
                            200 => ['description' => 'success'],
                        ],
                    ]],
                    '/api/test-from-yaml2' => ['get' => [
                        'responses' => [
                            200 => ['description' => 'success'],
                        ],
                    ]],
                ],
                'components' => [
                    'schemas' => [
                        'Test' => [
                            'type' => 'string',
                        ],

                        // Ensures https://github.com/nelmio/NelmioApiDocBundle/issues/1650 is working
                        'Pet' => [
                            'type' => 'object',
                        ],
                        'Cat' => [
                            'allOf' => [
                                ['$ref' => '#/components/schemas/Pet'],
                                ['type' => 'object'],
                            ],
                        ],
                        'AddProp' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                        ],
                    ],
                    'parameters' => [
                        'test' => [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                        ],
                    ],
                    'responses' => [
                        '201' => [
                            'description' => 'Awesome description',
                        ],
                    ],
                ],
            ],
            'areas' => [
                'default' => [
                    'path_patterns' => ['^/api(?!/admin)'],
                    'host_patterns' => ['^api\.'],
                ],
                'test' => [
                    'path_patterns' => ['^/test'],
                    'host_patterns' => ['^api-test\.'],
                    'documentation' => [
                        'info' => [
                            'title' => 'My Test App',
                        ],
                    ],
                ],
                'secured' => [
                    'path_patterns' => ['^/secured'],
                    'security' => [
                        'basicAuth' => [
                            'type' => 'http',
                            'scheme' => 'basic',
                        ],
                        'apiKeyAuth' => [
                            'type' => 'apiKey',
                            'in' => 'header',
                            'name' => 'X-API-Key',
                            'description' => 'API Key Authentication',
                        ],
                    ],
                ],
            ],
            'models' => [
                'names' => $models,
            ],
        ]);

        if (self::USE_JMS === $this->flag) {
            $c->loadFromExtension('jms_serializer', [
                'enum_support' => true,
            ]);
        }

        $def = new Definition(VirtualTypeClassDoesNotExistsHandlerDefinedDescriber::class);
        $def->addTag('nelmio_api_doc.model_describer');
        $c->setDefinition('nelmio.test.jms.virtual_type.describer', $def);

        $c->register('serializer.name_converter.custom', NameConverter::class)
            ->setDecoratedService('serializer.name_converter.metadata_aware')
            ->addArgument(new Reference('serializer.name_converter.custom.inner'));
    }

    public function getCacheDir(): string
    {
        return parent::getCacheDir().'/'.$this->flag;
    }

    public function getLogDir(): string
    {
        return parent::getLogDir().'/'.$this->flag;
    }
}
