<?php

namespace QT\Foundation\GraphQL\Validator\Rules;

use GraphQL\Validator\Rules\FieldsOnCorrectType as BaseFieldsOnCorrectType;

class RbacFieldsOnCorrectType extends BaseFieldsOnCorrectType
{
    /**
     * @param string   $fieldName
     * @param string   $type
     * @param string[] $suggestedTypeNames
     * @param string[] $suggestedFieldNames
     *
     * @return string
     */
    public static function undefinedFieldMessage(
        $fieldName,
        $type,
        array $suggestedTypeNames,
        array $suggestedFieldNames
    ) {
        return sprintf('没有权限访问: "%s".', $fieldName);
    }
}
