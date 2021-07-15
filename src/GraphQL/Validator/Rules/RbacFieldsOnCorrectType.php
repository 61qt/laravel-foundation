<?php

namespace QT\Foundation\GraphQL\Validator\Rules;

use GraphQL\Error\Error;
use QT\GraphQL\GraphQLManager;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Validator\ValidationContext;
use QT\Foundation\GraphQL\Definition\ModelType;

class RbacFieldsOnCorrectType
{
    public function __construct(protected GraphQLManager $manager)
    {

    }

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
        }

        return sprintf('字段"%s"不存在.', $node->name->value);
    }
}
