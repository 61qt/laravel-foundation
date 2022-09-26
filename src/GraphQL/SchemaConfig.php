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
        if (!empty($options['query'])) {
            $options['query'] = new Query($manager, $options['query']);
        }
        if (!empty($options['mutation'])) {
            $options['mutation'] = static::createMutation($manager, $options['mutation'], ['*' => true]);
        }
        if (empty($options['typeLoader'])) {
            $options['typeLoader'] = [$manager, 'getType'];
        }

        return static::create($options);
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
        if (!empty($options['query'])) {
            $options['query'] = new RbacQuery($manager, $options['query'], $resources);
        }
        if (!empty($options['mutation'])) {
            $options['mutation'] = static::createMutation($manager, $options['mutation'], $resources);
        }
        if (empty($options['typeLoader'])) {
            $options['typeLoader'] = [$manager, 'getType'];
        }

        return static::create($options);
    }

    /**
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

                $fields[$name] = compact('type', 'args', 'resolve', 'description');
            }
        }

        if (!empty($fields)) {
            return new ObjectType(['name' => 'mutation', 'fields' => $fields]);
        }
    }
}
