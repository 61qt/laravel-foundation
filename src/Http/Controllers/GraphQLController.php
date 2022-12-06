<?php

namespace QT\Foundation\Http\Controllers;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use QT\GraphQL\GraphQLManager;
use QT\Foundation\Http\Context;
use GraphQL\Language\AST\FieldNode;
use QT\Foundation\Exceptions\Error;
use QT\Foundation\ModuleRepository;
use Illuminate\Support\Facades\Auth;
use GraphQL\Executor\ExecutionResult;
use QT\Foundation\GraphQL\TypeFinder;
use GraphQL\Validator\Rules\QueryDepth;
use QT\Foundation\GraphQL\SchemaConfig;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use Illuminate\Contracts\Debug\ExceptionHandler;
use GraphQL\Validator\Rules\DisableIntrospection;

/**
 * GraphQLController
 *
 * @package QT\Foundation\Http\Controllers
 */
class GraphQLController
{
    /**
     * 无模块graphql请求
     *
     * @param Request $request
     * @return Response
     */
    public function graphql(Request $request)
    {
        $config  = config('graphql');
        $context = new Context($request, new Response(), ['graphql' => $config]);

        return $context->response->setContent($this->resolveGraphQL(
            $context,
            $this->getSchemaConfig($config, $context)
        ));
    }

    /**
     * 根据模块加载graphql语法
     *
     * @param Request $request
     * @param ModuleRepository $repository
     * @param string module
     * @return Response
     */
    public function module(Request $request, ModuleRepository $repository, string $module)
    {
        if (!$repository->has($module)) {
            throw new Error('NOT_FOUND', "{$module}模块不存在");
        }

        $config = $repository->config($module);
        // 允许模块自定义鉴权方式
        if (!empty($config['guard'])) {
            Auth::shouldUse($config['guard']);
        }

        $config  = $config->mergeRecursive(['graphql' => config('graphql')])->toArray();
        $context = new Context($request, new Response(), $config);

        return $context->response->setContent($this->resolveGraphQL(
            $context,
            $this->getSchemaConfig($config, $context)
        ));
    }

    /**
     * 根据请求上下文处理graphql语法
     *
     * @param Context $context
     * @param SchemaConfig $config
     * @return array
     */
    protected function resolveGraphQL(Context $context, SchemaConfig $config): array
    {
        $source    = $context->request->input('query', null);
        $variables = $context->request->input('variables', []);
        // 参数支持通过form表单提交
        $variables = is_string($variables)
            ? json_decode($variables, true)
            : $variables;

        $results = GraphQL::executeQuery(
            new Schema($config),
            $source,
            $this->getRootValue($context),
            $context,
            $variables,
            null,
            null,
            $this->getGraphQlRules($context)
        );

        return $this->handleResult($context->getRequest(), $results);
    }

    /**
     * @param Request $request
     * @param ExecutionResult $result
     * @return array
     */
    protected function handleResult(Request $request, ExecutionResult $result): array
    {
        if (!$request->has('catch') && !empty($result->errors)) {
            throw $result->errors[0];
        }

        $errors  = [];
        $handler = app(ExceptionHandler::class);
        foreach ($result->errors as $error) {
            // 记录错误信息
            $handler->report($error);
            // 整合错误信息
            foreach ($error->getNodes() as $node) {
                if (!$node instanceof FieldNode) {
                    continue;
                }

                if ($node->alias !== null) {
                    $name = $node->alias->value;
                } else {
                    $name = $node->name->value;
                }

                $errors[$name] = $error->getMessage();
                break;
            }
        }

        return [
            'code'   => 0,
            'msg'    => 'success',
            'data'   => $result->data ?? [],
            'errors' => $errors,
        ];
    }

    /**
     * 获取graphql type管理工具
     *
     * @param array $config
     * @return GraphQLManager
     */
    protected function getGraphQLManager(array $config): GraphQLManager
    {
        return tap(new GraphQLManager(), function ($manager) use ($config) {
            $manager->setTypeFinder(new TypeFinder($config));
        });
    }

    /**
     * 获取Schema config
     *
     * @param array $config
     * @param Context $context
     * @return SchemaConfig
     */
    protected function getSchemaConfig(array $config, Context $context): SchemaConfig
    {
        // 是要要根据权限生成graphql type
        $resources = $context->getValue('resources', []);
        $schema    = $context->getValue('graphql.schema');

        return $context->has('resources') && !empty($resources)
            ? SchemaConfig::rbac($this->getGraphQLManager($config), $schema, $resources)
            : SchemaConfig::make($this->getGraphQLManager($config), $schema);
    }

    /**
     * 获取根节点
     *
     * @param Context $context
     * @return mixed
     */
    protected function getRootValue(Context $context)
    {
        return null;
    }

    /**
     * 获取GraphQL校验规则
     *
     * @param Context $context
     * @return array
     */
    protected function getGraphQlRules(Context $context): array
    {
        return array_merge(DocumentValidator::defaultRules(), [
            QueryDepth::class           => new QueryDepth($context->getValue('graphql.max_depth')),
            QueryComplexity::class      => new QueryComplexity($context->getValue('graphql.complexity')),
            DisableIntrospection::class => new DisableIntrospection($context->getValue('graphql.introspection', 0)),
        ]);
    }
}
