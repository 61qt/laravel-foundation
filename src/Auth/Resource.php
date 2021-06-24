<?php

namespace QT\Foundation\Auth;

use QT\GraphQL\Contracts\Context;
use QT\Foundation\Auth\GraphQLAuthenticatable;

class Resource
{
    /**
     * 校验不允许访问字段权限
     *
     * @param GraphQLAuthenticatable $user
     * @param Context $context
     * @param string $type
     * @param array $selection
     * @return array
     */
    public static function getUnAuthColumns(
        GraphQLAuthenticatable $user,
        Context $context,
        string $type,
        array $selection
    ): array{
        // 获取用户拥有的权限
        $resources = $user->getResources($context);

        list($unAuthColumns) = self::checkGraphqlColumns($resources, $selection, $type);

        return $unAuthColumns;
    }

    /**
     * 检查selection是否有访问权限
     *
     * @param array $resources
     * @param string $type
     * @param array $selection
     * @param string $prefix
     * @return array
     */
    public static function checkGraphqlColumns(
        array $resources,
        array $selection,
        string $type,
        string $prefix = ''
    ): array{
        $unAuthColumns = [];
        $prefix .= $type . '-';
        foreach ($selection as $key => $value) {
            if (!isset($resources[$type][$key]) && !isset($resources[$type]['*'])) {
                $prefix = trim($prefix, '-');

                $unAuthColumns = array_merge([$prefix . '.' . $key], $unAuthColumns);
                unset($selection[$key]);
                continue;
            }
            // 为了避免多态关联查询导致的问题
            if (is_array($value) && !isset($value[0])) {
                list($unAuth, $selection[$key]) = self::checkGraphqlColumns($resources[$type], $value, $key, $prefix);

                $unAuthColumns = array_merge($unAuth, $unAuthColumns);
            }
        }

        return [$unAuthColumns, $selection];
    }

    /**
     * 获取可以查询的graphql字段
     *
     * @param GraphQLAuthenticatable $user
     * @param Context $context
     * @param string $type
     * @param array $selection
     * @return array
     */
    public static function getAllowColumns(
        GraphQLAuthenticatable $user,
        Context $context,
        string $type,
        array $selection
    ): array{
        // 获取用户拥有的权限
        $resources = $user->getResources($context);

        list(, $allowSection) = self::checkGraphqlColumns($resources, $selection, $type);

        return $allowSection;
    }

    /**
     * 检查mutation是否有访问权限
     *
     * @param GraphQLAuthenticatable $user
     * @param Context $context
     * @param string $mutation
     * @return boolean
     */
    public static function checkMutation(
        GraphQLAuthenticatable $user,
        Context $context,
        string $mutation
    ): bool {
        $resources = $user->getResources($context);

        return isset($resources[$mutation]);
    }

    /**
     * 检查导入是否有权限
     *
     * @param GraphQLAuthenticatable $user
     * @param Context $context
     * @param string $exportTask
     * @return boolean
     */
    public static function isAllowExport(
        GraphQLAuthenticatable $user,
        Context $context,
        string $exportTask
    ): bool {
        $resources = $user->getResources($context);

        return isset($resources[$exportTask]);
    }

    /**
     * 检查导入是否有权限
     *
     * @param GraphQLAuthenticatable $user
     * @param Context $context
     * @param string $importTask
     * @return boolean
     */
    public static function isAllowImport(
        GraphQLAuthenticatable $user,
        Context $context,
        string $importTask
    ): bool {
        $resources = $user->getResources($context);

        return isset($resources[$importTask]);
    }
}
