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
use GraphQL\Validator\Rules\QueryDepth;
use QT\Foundation\GraphQL\SchemaConfig;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\DisableIntrospection;

class GraphQLController
{
    protected $namespaces = [
        "App\\GraphQL",
    ];

    /**
     * 无模块graphql请求
     *
     * @param Request $request
     * @return Response
     */
    public function graphql(Request $request)
    {
        $context = new Context($request, new Response, config('graphql'));

        return $context->response->setContent($this->resolveGraphQL(
            $context, $this->getSchemaConfig($context->getValue('schema'), $context)
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

        $this->namespaces[] = "{$config['namespace']}\\GraphQL";

        // 允许模块自定义鉴权方式
        if (!empty($config['guard'])) {
            Auth::shouldUse($config['guard']);
        }

        $config  = array_merge(config('graphql'), $config->get('graphql', []));
        $context = new Context($request, new Response, $config);

        return $context->response->setContent($this->resolveGraphQL(
            $context, $this->getSchemaConfig($context->getValue('schema'), $context)
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
     * @return GraphQLManager
     */
    protected function getGraphQLManager(): GraphQLManager
    {
        return tap(new GraphQLManager, function ($manager) {
            $manager->setTypeFinder([$this, 'loadType']);
        });
    }

    /**
     * GraphQLManager文件查询回调
     *
     * @param string $name
     * @param string $space
     * @param GraphQLManager $manager
     */
    public function loadType($name, $space, GraphQLManager $manager)
    {
        foreach ($this->namespaces as $namespace) {
            $type = sprintf('%s\\%s\\%s', $namespace, $space, ucfirst($name));

            if (!class_exists($type)) {
                throw new RuntimeException("{$type} Class Not Found");
            }

            return app($type, compact('manager'));
        }
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
            ? SchemaConfig::rbac($this->getGraphQLManager(), $config, $resources)
            : SchemaConfig::make($this->getGraphQLManager(), $config);
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
            new QueryDepth($context->getValue('max_depth')),
            new QueryComplexity($context->getValue('complexity')),
            new DisableIntrospection($context->getValue('introspection', 0)),
        ];
    }
}
