<?php

namespace QT\Foundation\Http\Controllers;

use Error;
use GraphQL\GraphQL;
use RuntimeException;
use GraphQL\Type\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use QT\GraphQL\GraphQLManager;
use QT\Foundation\Http\Context;
use QT\Foundation\ModuleRepository;
use Illuminate\Support\Facades\Auth;
use QT\Foundation\GraphQL\TypeFinder;
use GraphQL\Validator\Rules\QueryDepth;
use QT\Foundation\GraphQL\SchemaConfig;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\DisableIntrospection;

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
        $context = new Context($request, new Response, ['graphql' => config('graphql')]);

        return $context->response->setContent($this->resolveGraphQL(
            $context, $this->getSchemaConfig($context->getValue('graphql.schema'), $context)
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
    public function module(Request $request, ModuleRepository $repository, $module)
    {
        $config = $repository->config($module);

        if (empty($config)) {
            throw new Error('NOT_FOUND', "{$module}模块不存在");
        }

        // 允许模块自定义鉴权方式
        if (!empty($config['guard'])) {
            Auth::shouldUse($config['guard']);
        }

        $config  = $config->mergeRecursive(['graphql' => config('graphql')]);
        $context = new Context($request, new Response, $config->toArray());

        return $context->response->setContent($this->resolveGraphQL(
            $context, $this->getSchemaConfig($context->getValue('graphql.schema'), $context)
        ));
    }

    /**
     * 根据请求上下文处理graphql语法
     *
     * @param Context $context
     * @return array
     */
    protected function resolveGraphQL(Context $context, SchemaConfig $config)
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

        if (!empty($results->errors)) {
            throw $results->errors[0];
        }

        return [
            'code' => 0,
            'msg'  => 'success',
            'data' => $results->data,
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
        return tap(new GraphQLManager, function ($manager) use ($config) {
            $manager->setTypeFinder(new TypeFinder($config));
        });
    }

    /**
     * 获取Schema config
     *
     * @param Context $context
     * @return SchemaConfig
     */
    protected function getSchemaConfig(array $config, Context $context): SchemaConfig
    {
        // 是要要根据权限生成graphql type
        $resources = $context->getValue('resources', []);

        return $context->has('resources') && !empty($resources)
            ? SchemaConfig::rbac($this->getGraphQLManager($config), $config, $resources)
            : SchemaConfig::make($this->getGraphQLManager($config), $config);
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
    protected function getGraphQlRules(Context $context)
    {
        return [
            new QueryDepth($context->getValue('graphql.max_depth')),
            new QueryComplexity($context->getValue('graphql.complexity')),
            new DisableIntrospection($context->getValue('graphql.introspection', 0)),
        ];
    }
}
