<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Resolvable;
use QT\GraphQL\Definition\ModelType;
use GraphQL\Type\Definition\ObjectType;

class Query extends ObjectType
{
    public $name = 'query';

    protected $types = [];

    public function __construct(protected GraphQLManager $manager, array $names)
    {
        $this->initTypes($names);

        parent::__construct(['fields' => $this->types]);
    }

    protected function initTypes(array $names)
    {
        foreach ($names as $name) {
            $this->registerType($this->manager->getType($name));
        }
    }

    protected function registerType($type)
    {
        if ($type instanceof Resolvable) {
            $this->types[$type->name] = [
                'type'        => $type,
                'resolve'     => [$type, 'resolve'],
                'args'        => $type->getArgs($this->manager),
                'description' => $type->description,
            ];
        }

        if ($type instanceof ModelType) {
            foreach ($type->getExtraTypes($this->manager) as $type) {
                $this->manager->setType($type);

                $this->registerType($type);
            }
        }
    }
}

