<?php

namespace QT\Foundation\Traits;

use Illuminate\Contracts\Validation\Factory;

/**
 * Resolver初始化
 * 
 * @package QT\Foundation\Traits
 */
trait ResolverConstruct
{
    /**
     * Resolver constructor.
     */
    public function __construct()
    {
        parent::__construct($this->getModel(), app(Factory::class));
    }

    /**
     * 获取Resolver绑定的model
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    abstract public function getModel();
}