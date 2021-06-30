<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use GraphQL\Type\SchemaConfig as BaseSchemaConfig;

class SchemaConfig extends BaseSchemaConfig
{
    public static function make(GraphQLManager $manager, array $options)
    {
        return static::create($options);
    }

    public static function rbac(GraphQLManager $manager, array $options, array $resources)
    {
        return static::create($options);
    }
}
