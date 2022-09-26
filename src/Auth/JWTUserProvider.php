<?php

namespace QT\Foundation\Auth;

use RuntimeException;
use QT\Foundation\Contracts\JWTSubject;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

/**
 * JWTUserProvider
 * 
 * @package QT\Foundation\Auth
 */
class JWTUserProvider extends EloquentUserProvider
{
    /**
     * Create a new database user provider.
     *
     * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
     * @param  string  $model
     * @return void
     */
    public function __construct(HasherContract $hasher, $model)
    {
        if (!is_subclass_of($model, JWTSubject::class)) {
            throw new RuntimeException('Must implement interface QT\Foundation\Auth\Contracts\JWTSubject');
        }

        $this->model = $model;
        $this->hasher = $hasher;
    }
}