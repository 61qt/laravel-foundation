<?php

namespace QT\Foundation\GraphQL;

use RuntimeException;
use QT\GraphQL\GraphQLManager;
use Illuminate\Support\Collection;

/**
 * GraphQLManager文件查询
 * 
 * @package QT\Foundation\GraphQL
 */
class TypeFinder
{
    /**
     * 可用的命名空间
     *
     * @var array
     */
    protected $namespaces = [
        "App\\GraphQL",
    ];

    /**
     * @param array $config
     * @return void
     */
    public function __construct(protected array | Collection $config)
    {
        array_unshift($this->namespaces, "{$config['namespace']}\\GraphQL");
    }

    /**
     * @param string $name
     * @param string $space
     * @param GraphQLManager $manager
     * @return \GraphQL\Type\Definition\Type
     */
    public function __invoke($name, $space, GraphQLManager $manager)
    {
        foreach ($this->namespaces as $namespace) {
            $type = sprintf('%s\\%s\\%s', $namespace, $space, ucfirst($name));

            if (class_exists($type)) {
                return app($type, compact('manager'));
            }
        }

        throw new RuntimeException("{$name} Type Not Found");
    }
}
