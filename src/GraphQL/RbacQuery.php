<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use QT\Foundation\GraphQL\Type\ModelType;

class RbacQuery extends Query
{
    protected $resources = [];

    public function __construct(protected GraphQLManager $manager, array $names, array $resources = [])
    {
        $this->resources = $resources;

        parent::__construct($manager, $names);
    }

    protected function initTypes(array $names)
    {
        foreach ($names as $name) {
            if (empty($this->resources[$name])) {
                continue;
            }

            $type = $this->manager->getType($name);

            $this->registerType($type);

            if ($type instanceof ModelType) {
                $type->canAccess = $this->resources[$name];
            }
        }
    }
}
