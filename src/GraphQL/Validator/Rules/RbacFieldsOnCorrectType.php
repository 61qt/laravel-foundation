<?php

namespace QT\Foundation\GraphQL\Validator\Rules;

use Throwable;
use GraphQL\Error\Error;
use QT\GraphQL\GraphQLManager;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\FieldNode;
use QT\Foundation\GraphQL\RbacQuery;
use GraphQL\Validator\ValidationContext;
use QT\Foundation\GraphQL\Definition\ModelType;

/**
 * 根据角色拥有的权限检查字段是否可用
 * 
 * @package QT\Foundation\GraphQL\Validator\Rules
 */
class RbacFieldsOnCorrectType
{
    /**
     * @param GraphQLManager $manager
     */
    public function __construct(protected GraphQLManager $manager)
    {
    }

    /**
     * @param ValidationContext $context
     */
    public function getVisitor(ValidationContext $context)
    {
        return [
            NodeKind::FIELD => function (FieldNode $node) use ($context): void {
                $type = $context->getParentType();

                if (!$type) {
                    return;
                }

                $fieldDef = $context->getFieldDef();

                if ($fieldDef) {
                    return;
                }

                $msg = static::undefinedFieldMessage($node, $type, $this->manager);

                $context->reportError(new Error($msg, [$node]));
            },
        ];
    }

    /**
     * @param FieldNode $node
     * @param string $type
     * @param GraphQLManager $manager
     * @return string
     */
    public static function undefinedFieldMessage(FieldNode $node, $type, $manager)
    {
        if ($type instanceof ModelType) {
            $fields = $type->getDataStructure($manager);

            if (!empty($fields[$node->name->value])) {
                return sprintf('没有权限访问: "%s".', $node->name->value);
            }
        } if ($type instanceof RbacQuery) {
            try {
                // 能取回type说明type存在但是没有权限
                $manager->getType($node->name->value);

                return sprintf('没有权限访问: "%s".', $node->name->value);
            } catch (Throwable $e) {
                // 如果取不到type,就原样返回错误信息
                return $e->getMessage();
            }
        }

        return sprintf('字段"%s"不存在.', $node->name->value);
    }
}
