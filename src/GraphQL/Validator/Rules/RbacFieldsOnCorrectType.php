<?php

namespace QT\Foundation\GraphQL\Validator\Rules;

use Throwable;
use QT\GraphQL\GraphQLManager;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\FieldNode;
use QT\Foundation\GraphQL\RbacQuery;
use GraphQL\Validator\ValidationContext;
use GraphQL\Error\Error as GraphQLError;
use QT\Foundation\GraphQL\Definition\ModelType;
use QT\Foundation\Exceptions\TypeNotFoundException;

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
     * 获取访问信息
     *
     * @param ValidationContext $context
     * @return array
     */
    public function getVisitor(ValidationContext $context): array
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

                $error = new TypeNotFoundException($this->undefinedFieldMessage($node, $type));

                $context->reportError(new GraphQLError(nodes: [$node], previous: $error));
            },
        ];
    }

    /**
     * @param FieldNode $node
     * @param mixed $type
     * @return string
     */
    protected function undefinedFieldMessage(FieldNode $node, mixed $type): string
    {
        if ($type instanceof ModelType) {
            $fields = $type->getDataStructure($this->manager);

            if (!empty($fields[$node->name->value])) {
                return sprintf('没有权限访问: "%s".', $node->name->value);
            }
        }

        if ($type instanceof RbacQuery) {
            try {
                // 能取回type说明type存在但是没有权限
                $this->manager->getType($node->name->value);

                return sprintf('没有权限访问: "%s".', $node->name->value);
            } catch (Throwable $e) {
                // 如果取不到type,就原样返回错误信息
                return $e->getMessage();
            }
        }

        return sprintf('字段"%s"不存在.', $node->name->value);
    }
}
