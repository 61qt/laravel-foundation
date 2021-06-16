<?php

namespace QT\Foundation\Http\Controllers;

use Error;
use GraphQL\GraphQL;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use QT\GraphQL\GraphQLManager;
use QT\Foundation\Http\Context;
use QT\Foundation\GraphQL\Schema;
use QT\Foundation\ModuleRepository;
use Illuminate\Support\Facades\Auth;
use GraphQL\Validator\Rules\QueryDepth;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\DisableIntrospection;

class GraphQLController
{
    protected $namespaces = [
        "App\\GraphQL",
    ];

    public function graphql(Request $request, ModuleRepository $repository, $module)
    {
        $config = $repository->config($module);

        if (empty($config)) {
            throw new Error('NOT_FOUND', "{$module}模块不存在");
        }

        $context = new Context($request, new Response, $config);

        // 允许模块自定义鉴权方式
        if (!empty($config['guard'])) {
            Auth::shouldUse($config['guard']);
        }

        $this->namespaces[] = "{$config['namespace']}\\GraphQL";

        return $context->response->setContent($this->resolveGraphQL($context));
    }

    protected function resolveGraphQL(Context $context)
    {
        $manager   = $this->getGraphQLManager();
        $source    = $context->request->input('query', null);
        $variables = $context->request->input('variables', []);
        // 参数支持通过form表单提交
        $variables = is_string($variables)
            ? json_decode($variables, true)
            : $variables;

        $results = GraphQL::executeQuery(
            $this->getSchema($manager, $context),
            $source,
            $this->getRootValue($context),
            $context,
            $variables,
            null,
            null,
            $this->getGraphQlRules($context)
        );

        if (count($results->errors) > 0) {
            throw $results->errors[0];
        }

        return $results;
    }

    protected function getGraphQLManager(): GraphQLManager
    {
        return tap(new GraphQLManager, function ($manager) {
            $manager->setTypeFinder([$this, 'loadType']);
        });
    }

    public function loadType($name, $space, GraphQLManager $manager)
    {
        foreach ($this->namespaces as $namespace) {
            $type = sprintf('%s\\%s\\%s', $namespace, $space, ucfirst($name));

            if (class_exists($type)) {
                return new $type($manager);
            }
        }
    }

    protected function getSchema(GraphQLManager $manager, Context $context): Schema
    {
        return Schema::create($manager, $context->getValue('graphql_schema'));
    }

    protected function getRootValue(Context $context)
    {
        return null;
    }

    protected function getGraphQlRules(Context $context)
    {
        return [
            new QueryDepth($context->getValue('max_depth')),
            new QueryComplexity($context->getValue('complexity')),
            new DisableIntrospection($context->getValue('introspection', 0)),
        ];
    }
}
