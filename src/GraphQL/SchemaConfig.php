<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\SchemaConfig as BaseSchemaConfig;

/**
 * SchemaConfig
 *
 * @package QT\Foundation\GraphQL
 */
class SchemaConfig extends BaseSchemaConfig
{
    /**
     * 生成schema
     *
     * @param GraphQLManager $manager
     * @param array $options
     */
    public static function make(GraphQLManager $manager, array $options)
    {
        $config = static::create($options);

        if (!empty($options['query'])) {
            $config->setQuery(new Query($manager, $options['query']));
        }
        if (!empty($options['mutation'])) {
            $config->setMutation(static::createMutation($manager, $options['mutation'], ['*' => true]));
        }
        if (empty($options['typeLoader'])) {
            $config->setTypeLoader([$manager, 'getType']);
        }

        return $config;
    }

    /**
     * 根据角色权限生成schema
     *
     * @param GraphQLManager $manager
     * @param array $options
     * @param array $resources
     */
    public static function rbac(GraphQLManager $manager, array $options, array $resources)
    {
        $config = static::create($options);

        if (!empty($options['query'])) {
            $config->setQuery(new RbacQuery($manager, $options['query'], $resources));
        }
        if (!empty($options['mutation'])) {
            $config->setMutation(static::createMutation($manager, $options['mutation'], $resources));
        }
        if (empty($options['typeLoader'])) {
            $config->setTypeLoader([$manager, 'getType']);
        }

        return $config;
    }

    /**
     * 根据文件获取具体的mutation方法
     *
     * @param GraphQLManager $manager
     * @param array $files
     * @param array $resources
     */
    protected static function createMutation(GraphQLManager $manager, array $files, array $resources = [])
    {
        $fields = [];
        $skip   = isset($resources['*']);
        foreach ($files as $file) {
            $mutation = $manager->getMutation($file);
            $configs  = $mutation->getMutationConfig();

            foreach ($configs as $name => [$type, $args, $resolve, $description]) {
                if (!$skip && empty($resources[$name])) {
                    continue;
                }

                $fields[$name] = [
                    'type'        => $type,
                    'args'        => $args,
                    'resolve'     => $resolve,
                    'description' => $description,
                ];
            }
        }

        if (!empty($fields)) {
            return new ObjectType(['name' => 'mutation', 'fields' => $fields]);
        }
    }
}
