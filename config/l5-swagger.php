<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureDocumentationAccess;
use L5Swagger\Generator;

$environment = (string) env('APP_ENV', 'production');

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => env('L5_SWAGGER_API_TITLE', 'BasePHP API'),
            ],

            'routes' => [
                'api' => env('L5_SWAGGER_ROUTE', 'api/documentation'),
            ],

            'paths' => [
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                'swagger_ui_assets_path' => env(
                    'L5_SWAGGER_UI_ASSETS_PATH',
                    'vendor/swagger-api/swagger-ui/dist/'
                ),
                'docs_json' => 'api-docs.json',
                'docs_yaml' => 'api-docs.yaml',
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                'annotations' => [
                    base_path('app/OpenApi'),
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',

            'middleware' => [
                'api' => [EnsureDocumentationAccess::class],
                'asset' => [EnsureDocumentationAccess::class],
                'docs' => [EnsureDocumentationAccess::class],
                'oauth2_callback' => [EnsureDocumentationAccess::class],
            ],

            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'excludes' => [],
        ],

        'scanOptions' => [
            'default_processors_configuration' => [],
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => env(
                'L5_SWAGGER_OPEN_API_SPEC_VERSION',
                Generator::OPEN_API_DEFAULT_SPEC_VERSION
            ),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [
                'passport' => [
                    'type' => 'oauth2',
                    'description' => 'Autenticação OAuth2 via Laravel Passport usando Authorization Code + PKCE para usuários humanos e Client Credentials para integrações sistema-a-sistema.',
                    'flows' => [
                        'authorizationCode' => [
                            'authorizationUrl' => env('APP_URL', 'http://localhost:8000').'/oauth/authorize',
                            'tokenUrl' => env('APP_URL', 'http://localhost:8000').'/oauth/token',
                            'refreshUrl' => env('APP_URL', 'http://localhost:8000').'/oauth/token',
                            'scopes' => [
                                'user.profile' => 'Permite consultar dados do usuário autenticado.',
                                'user.password.change' => 'Permite alterar a própria senha.',
                                'tenant.access' => 'Permite acessar recursos vinculados a tenant.',
                                'admin.full' => 'Permite acessar rotas administrativas.',
                                'tenants.read' => 'Permite consultar tenants.',
                                'tenants.write' => 'Permite criar e manter tenants.',
                                'users.read' => 'Permite consultar usuários globais.',
                                'users.write' => 'Permite criar e manter usuários globais.',
                                'tenant.users.read' => 'Permite consultar vínculos usuário x tenant.',
                                'tenant.users.write' => 'Permite criar e manter vínculos usuário x tenant.',
                                'queues.read' => 'Permite consultar filas, jobs e falhas.',
                                'queues.write' => 'Permite executar ações operacionais em filas.',
                                'emails.read' => 'Permite consultar envios de e-mail.',
                                'emails.write' => 'Permite enviar e reprocessar e-mails.',
                            ],
                        ],
                        'clientCredentials' => [
                            'tokenUrl' => env('APP_URL', 'http://localhost:8000').'/oauth/token',
                            'scopes' => [
                                'system.health' => 'Permite consultar endpoints operacionais sistema-a-sistema.',
                            ],
                        ],
                    ],
                ],
                'tenantHeader' => [
                    'type' => 'apiKey',
                    'description' => 'Código do tenant ativo. Exemplo: tenant-main.',
                    'name' => 'X-Tenant-Id',
                    'in' => 'header',
                ],
            ],

            'security' => [],
        ],

        'access' => [
            'public' => env('L5_SWAGGER_PUBLIC', in_array($environment, ['local', 'testing'], true)),
            'allowed_ips' => array_values(array_filter(array_map(
                static fn (string $ip): string => trim($ip),
                explode(',', (string) env('L5_SWAGGER_ALLOWED_IPS', '127.0.0.1,::1'))
            ))),
        ],

        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
        'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', 'alpha'),
        'validator_url' => null,

        'ui' => [
            'display' => [
                'dark_mode' => env('L5_SWAGGER_UI_DARK_MODE', false),
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter' => env('L5_SWAGGER_UI_FILTERS', true),
            ],

            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', false),

                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => true,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('APP_URL', 'http://localhost:8000'),
        ],
    ],
];
