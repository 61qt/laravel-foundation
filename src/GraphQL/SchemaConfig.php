<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\SchemaConfig as BaseSchemaConfig;

class SchemaConfig extends BaseSchemaConfig
{
    public static function make(GraphQLManager $manager, array $options)
    {
        if (!empty($options['query'])) {
            $options['query'] = new Query($manager, $options['query']);
        }
        if (!empty($options['mutation'])) {
            $options['mutation'] = static::createMutation($manager, $options['mutation'], ['*' => true]);
        }

        return static::create($options);
    }

    public static function rbac(GraphQLManager $manager, array $options, array $resources)
    {
        if (!empty($options['query'])) {
            $options['query'] = new RbacQuery($manager, $options['query'], $resources);
        }
        if (!empty($options['mutation'])) {
            $options['mutation'] = static::createMutation($manager, $options['mutation'], $resources);
        }

        return static::create($options);
    }

    protected static function createMutation(GraphQLManager $manager, array $files, $resources = [])
    {
        $fields = [];
        $skip   = isset($resources['*']);
        foreach ($files as $file) {
            $mutation = $manager->getMutation($file);
            $configs  = $mutation->getMutationConfig();

            foreach ($configs as $name => [$type, $args, $resolve]) {
                if (!$skip && empty($resources[$name])) {
                    continue;
                }

                $fields[$name] = compact('type', 'args', 'resolve');
            }
        }

        if (!empty($fields)) {
            return new ObjectType(['name' => 'mutation', 'fields' => $fields]);
        }
    }
}
