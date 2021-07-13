<?php

namespace QT\Foundation\Contracts;

interface JWTSubject
{
    public function getJWTIdentifier();

    public function getJWTCustomClaims();
}