<?php

namespace QT\Foundation\GraphQL;

use GraphQL\Type\SchemaConfig;
use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Resolvable;
use QT\GraphQL\Definition\ModelType;
use GraphQL\Type\Schema as BaseSchema;
use GraphQL\Type\Definition\ObjectType;

class Schema extends BaseSchema
{
    public static function create(GraphQLManager $manager, array $options): static
    {
        $config = new SchemaConfig($options);

        if (!empty($options['query'])) {
            $config->query = static::getQuery($manager, $options['query']);
        }

        if (!empty($options['mutation'])) {
            $config->mutation = static::getMutation($manager, $options['mutation']);
        }

        if (empty($config->typeLoader)) {
            $config->typeLoader = [$manager, 'getType'];
        }

        return new self($config);
    }

    protected static function getQuery(GraphQLManager $manager, array $names): ObjectType
    {
        $fields = [];
        foreach ($names as $name) {
            $type   = $manager->getType($name);
            $fields = static::registerType($fields, $type, $manager);

            if ($type instanceof ModelType) {
                foreach ($type->getExtraTypes($manager) as $type) {
                    $manager->setType($type);

                    $fields = static::registerType($fields, $type, $manager);
                }
            }
        }

        return new ObjectType(['name' => 'query', 'fields' => $fields]);
    }

    protected static function getMutation(GraphQLManager $manager, array $names): ObjectType
    {
        $fields = [];
        foreach ($names as $name) {
            $mutation = $manager->getMutation($name);
            $configs  = $mutation->getMutationConfig();

            foreach ($configs as $name => [$type, $args, $resolve]) {
                $fields[$name] = compact('type', 'args', 'resolve');
            }
        }

        return new ObjectType(['name' => 'mutation', 'fields' => $fields]);
    }

    protected static function registerType(array $fields, $type, GraphQLManager $manager): array
    {
        if (!$type instanceof Resolvable) {
            return $fields;
        }

        return array_merge($fields, [$type->name => [
            'type'    => $type,
            'resolve' => [$type, 'resolve'],
            'args'    => $type->getArgs($manager),
        ]]);
    }
}