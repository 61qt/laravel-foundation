<?php
namespace App\Auth;

use QT\GraphQL\Contracts\Context;

interface GraphQLAuthenticatable
{
    /**
     * 获取用户拥有的菜单
     *
     * @param Context $context
     * @return array
     */
    public function getUserMenus(Context $context): array;

    /**
     * 获取用户拥有的权限
     *
     * @param Context $context
     * @return array
     */
    public function getResources(Context $context): array;
}
