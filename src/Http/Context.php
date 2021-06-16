<?php

namespace QT\Foundation\Http;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use QT\GraphQL\Contracts\Context as ContextContract;

class Context implements ContextContract
{
    /**
     * Context constructor.
     * @param Request $request
     * @param Response $response
     * @param Collection $config
     */
    public function __construct(
        public Request $request,
        public Response $response,
        protected ?Collection $config = null,
    ) {
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string|int $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }
}
