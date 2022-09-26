<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use QT\GraphQL\Contracts\Resolvable;
use QT\GraphQL\Definition\ModelType;
use GraphQL\Type\Definition\ObjectType;

/**
 * 可以查询的type集合
 *
 * @package QT\Foundation\GraphQL
 */
class Query extends ObjectType
{
    /**
     * 默认为query
     *
     * @var string
     */
    public $name = 'query';

    /**
     * 可用type
     *
     * @var array
     */
    protected $types = [];

    /**
     * @param GraphQLManager $manager
     * @param array $names
     */
    public function __construct(protected GraphQLManager $manager, array $names)
    {
        $this->initTypes($names);

        parent::__construct(['fields' => $this->types]);
    }

    /**
     * 初始化可用types
     *
     * @param array $names
     */
    protected function initTypes(array $names)
    {
        foreach ($names as $name) {
            $this->registerType($this->manager->getType($name));
        }
    }

    /**
     * @param mixed $type
     */
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
