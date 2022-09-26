<?php

namespace QT\Foundation\GraphQL;

use QT\GraphQL\GraphQLManager;
use QT\Foundation\GraphQL\Definition\ModelType;

/**
 * 基于角色控制的可查询集合
 * 
 * @package QT\Foundation\GraphQL
 */
class RbacQuery extends Query
{
    /**
     * 可以当前用户请求的资源
     * 
     * @var array
     */
    protected $resources = [];

    /**
     * @param GraphQLManager $manager
     * @param array $names
     * @param array $resources
     */
    public function __construct(protected GraphQLManager $manager, array $names, array $resources = [])
    {
        $this->resources = $resources;

        parent::__construct($manager, $names);
    }

    /**
     * 初始化可用types
     *
     * @param array $names
     */
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
