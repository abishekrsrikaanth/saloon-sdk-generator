<?php

declare(strict_types=1);

namespace Crescat\SaloonSdkGenerator\Data\Generator;

use Composer\Autoload\ClassLoader;
use Exception;
use Illuminate\Support\Arr;
use ReflectionClass;

class Config
{
    const CONFIG_OPTS = [
        'connectorName', 'namespace', 'resourceNamespaceSuffix', 'requestNamespaceSuffix', 'dtoNamespaceSuffix', 'fallbackResourceName',
        'baseResourceNamespace',
        'type', 'outputDir', 'force',
        'ignoredQueryParams', 'ignoredBodyParams', 'extra',
    ];

    const REQUIRED_OPTS = ['connectorName', 'namespace'];

    /**
     * @param  string|null  $connectorName The name of the connector class.
     * @param  string|null  $namespace The main namespace for the generated SDK.
     * @param  string|null  $resourceNamespaceSuffix The suffix for the resource namespace.
     * @param  string|null  $requestNamespaceSuffix The suffix for the request namespace.
     * @param  string|null  $responseNamespaceSuffix The suffix for the response namespace.
     * @param  string|null  $dtoNamespaceSuffix The suffix for the DTO namespace.
     * @param  string|null  $baseResourceNamespace The namespace for the base resource class.
     * @param  string|null  $fallbackResourceName The default name to use for resources if none could be inferred from the specification.
     * @param  string|null  $type The type of API specification to parse.
     * @param  string|null  $outputDir The output directory where the generated code will be saved.
     * @param  bool|null  $force Whether to overwrite existing files.
     * @param  array  $ignoredQueryParams List of query parameters that should be ignored.
     * @param  array  $ignoredBodyParams List of body parameters that should be ignored.
     * @param  array  $extra Additional configuration for custom code generators.
     */
    public function __construct(
        public readonly ?string $connectorName,
        public readonly ?string $namespace,
        public readonly ?string $resourceNamespaceSuffix = 'Resource',
        public readonly ?string $requestNamespaceSuffix = 'Requests',
        public readonly ?string $responseNamespaceSuffix = 'Responses',
        public readonly ?string $dtoNamespaceSuffix = 'Dto',
        public readonly ?string $baseResourceNamespace = null,
        public readonly ?string $fallbackResourceName = 'Misc',

        public readonly ?string $type = 'postman',
        public readonly ?string $outputDir = './build',
        public readonly ?bool $force = false,

        public readonly array $ignoredQueryParams = [],
        public readonly array $ignoredBodyParams = [],
        public readonly array $extra = [],
    ) {
    }

    /**
     * Load configuration from a JSON file. If no path is provided, it will look for
     * a file named generator-config.json in the root project directory.
     *
     * @throws Exception
     */
    public static function load(?string $path = null, array $overrides = []): static
    {
        // Find the root project directory
        $reflection = new ReflectionClass(ClassLoader::class);
        $vendorDir = dirname($reflection->getFileName(), 3);
        $path ??= $vendorDir.'/generator-config.json';

        $file = file_get_contents($path);
        if ($file === false) {
            throw new Exception("Failed to open config file: $path");
        }
        try {
            $config = json_decode($file, true);
        } catch (Exception $e) {
            throw new Exception("Failed to parse config file: $path");
        }

        $missingKeys = array_diff(self::REQUIRED_OPTS, array_keys($config));
        $unknownKeys = array_diff(array_keys($config), self::CONFIG_OPTS);
        if (! empty($missingKeys)) {
            throw new Exception('Missing required config file keys: '.implode(', ', $missingKeys));
        }
        if (! empty($unknownKeys)) {
            echo '[WARNING] Unknown config file keys: '.implode(', ', $unknownKeys)."\n";
        }

        $getOpt = fn ($key, $default) => isset($overrides[$key]) ? $overrides[$key] : Arr::get($config, $key, $default ?? null);

        $outputDir = $getOpt('outputDir', './build');

        return new static(
            connectorName: $overrides['connectorName'] ?? $config['connectorName'],
            namespace: $overrides['namespace'] ?? $config['namespace'],
            resourceNamespaceSuffix: $getOpt('resourceNamespaceSuffix', 'Resource'),
            requestNamespaceSuffix: $getOpt('requestNamespaceSuffix', 'Requests'),
            responseNamespaceSuffix: $getOpt('responseNamespaceSuffix', 'Responses'),
            dtoNamespaceSuffix: $getOpt('dtoNamespaceSuffix', 'Dto'),
            baseResourceNamespace: $getOpt('baseResourceNamespace', null),
            fallbackResourceName: $getOpt('fallbackResourceName', 'Misc'),

            type: $getOpt('type', 'postman'),
            outputDir: trim($outputDir, '/'),
            force: $getOpt('force', false),

            ignoredQueryParams: $getOpt('ignoredQueryParams', []),
            ignoredBodyParams: $getOpt('ignoredBodyParams', []),
            extra: $getOpt('extra', []),
        );
    }
}
