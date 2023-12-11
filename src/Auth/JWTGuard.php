<?php

namespace QT\Foundation\Auth;

use Throwable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use QT\Foundation\Exceptions\Error;
use Illuminate\Contracts\Auth\Guard;
use QT\Foundation\Traits\SingleSignOn;
use QT\Foundation\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use QT\Foundation\Contracts\GraphQLAuthenticatable;

/**
 * JWT
 *
 * @package QT\Foundation\Auth
 */
class JWTGuard implements Guard, StatefulGuard
{
    use GuardHelpers;
    use SingleSignOn;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $alg;

    /**
     * @var string
     */
    protected $key = null;

    /**
     * @var Authenticatable|JWTSubject|GraphQLAuthenticatable
     */
    protected $user = null;

    /**
     * @var Authenticatable|JWTSubject|GraphQLAuthenticatable
     */
    protected $lastAttempted;

    /**
     * @var object
     */
    protected $payload;

    /**
     * @var JWTUserProvider
     */
    protected $provider;

    /**
     * 从input中获取token的key
     *
     * @var string
     */
    protected $inputKey;

    /**
     * 验证方式(graphql/restful)
     *
     * @var string
     */
    protected $guard;

    /**
     * 当前使用的token
     *
     * @var string
     */
    protected $token;

    /**
     * token有效时间(单位/分)
     *
     * @var int
     */
    protected $ttl;

    /**
     * 管理员token有效时间(单位/分)
     *
     * @var int
     */
    protected $ttlAdmin;

    /**
     * 允许刷新token的时间范围(单位/分)
     *
     * @var int
     */
    protected $refreshTtl;

    /**
     * 允许刷新管理员token的时间范围(单位/分)
     *
     * @var int
     */
    protected $refreshTtlAdmin;

    /**
     * 是否已退出
     *
     * @var bool
     */
    protected $loggedOut = false;

    /**
     * 主键名称
     *
     * @var mixed|string
     */
    protected $keyName = 'id';

    /**
     * 是否从db中获取过用户信息
     *
     * @var bool
     */
    protected $userLoaded = false;

    /**
     * JWTGuard constructor.
     *
     * @param Request $request
     * @param JWTUserProvider $provider
     * @param string $guard
     * @param array $options
     */
    public function __construct(
        Request $request,
        JWTUserProvider $provider,
        string $guard,
        array $options = []
    ) {
        $this->request  = $request;
        $this->provider = $provider;
        $this->guard    = $guard;
        $this->keyName  = $options['keyName'] ?? 'id';
        $this->inputKey = $options['inputKey'] ?? 'api_token';

        if (empty($options['alg'])) {
            throw new Error('FORBIDDEN', '加密算法不能为空');
        }

        $this->alg = $options['alg'];

        if (empty($options['guards'][$guard])) {
            throw new Error('FORBIDDEN', 'Guard配置错误');
        }

        $guardConfig = $options['guards'][$guard];

        if (empty($guardConfig['key'])) {
            throw new Error('FORBIDDEN', 'encode key 不能为空');
        }

        $this->key             = $guardConfig['key'];
        $this->ttl             = $guardConfig['ttl'] ?? 10;
        $this->refreshTtl      = $guardConfig['refresh_ttl'] ?? $this->ttl;
        $this->ttlAdmin        = $options['guards']['admin']['ttl'] ?? $this->ttl;
        $this->refreshTtlAdmin = $options['guards']['admin']['refresh_ttl'] ?? $this->ttlAdmin;
    }

    /**
     * 确定当前用户是否已通过身份验证
     *
     * @return bool
     */
    public function check()
    {
        if ($this->user) {
            return true;
        }

        if (empty($this->getToken())) {
            return false;
        }

        try {
            $payload = $this->getPayload();
        } catch (Throwable $e) {
            return false;
        }

        if (empty($payload->{$this->keyName})) {
            return false;
        }

        return $this->inWhitelist($payload->{$this->keyName}, $this->token);
    }

    /**
     * 确定当前用户是否为访客
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * 获取用户
     *
     * @throws Error
     * @return JWTSubject|Authenticatable|GraphQLAuthenticatable|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return;
        }

        if ($this->user !== null) {
            return $this->user;
        }

        if ($this->guest()) {
            return null;
        }

        $user = $this->provider->createModel();

        $payload = $this->getPayload();
        foreach ($user->getJWTCustomClaims() as $name => $_) {
            $user->{$name} = $payload->{$name} ?? null;
        }

        $user->exists = true;

        return $this->user = $user;
    }

    /**
     * 从当前token中获取用户唯一id.
     *
     * @return int|null
     */
    public function id()
    {
        if ($this->loggedOut) {
            return;
        }

        return $this->user() !== null
            ? $this->user()->getAuthIdentifier()
            : $this->getPayload()?->{$this->keyName};
    }

    /**
     * 检查登录账户信息是否正确
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($this->lastAttempted, $credentials);
    }

    /**
     * 用给定的凭据对用户进行身份验证.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        if (!$this->validate($credentials)) {
            return false;
        }

        $this->login($this->lastAttempted, $remember);

        return true;
    }

    /**
     * 用户登录
     *
     * @param Authenticatable|JWTSubject $user
     * @param bool $remember
     * @return void
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $ttl        = $this->ttl;
        $refreshTtl = $this->refreshTtl;
        // 管理员的token生效时间
        if (method_exists($user, 'isAdmin') && call_user_func([$user, 'isAdmin'])) {
            $ttl        = $this->ttlAdmin;
            $refreshTtl = $this->refreshTtlAdmin;
        }

        $this->setUser($user);
        // 生成token
        $this->token = $this->generateToken($this->user(), $ttl, $refreshTtl);

        $this->addToWhitelist($this->token, $user->id, $ttl);
    }

    /**
     * 用户id登录
     *
     * @param mixed $id
     * @param bool $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|bool
     */
    public function loginUsingId($id, $remember = false)
    {
        if (($user = $this->provider->retrieveById($id)) !== null) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        if (!$this->validate($credentials)) {
            return false;
        }

        $this->setUser($this->lastAttempted);

        return true;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param mixed $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|bool
     */
    public function onceUsingId($id)
    {
        $user = $this->provider->retrieveById($id);

        if ($user === null) {
            return false;
        }

        $this->setUser($user);

        return $user;
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return false;
    }

    /**
     * 退出登录.
     *
     * @return void
     */
    public function logout()
    {
        if ($this->user() === null) {
            return;
        }

        $this->invalidate($this->id());

        $this->user      = null;
        $this->token     = null;
        $this->payload   = null;
        $this->loggedOut = true;
    }

    /**
     * 检查登录凭证是否与数据库内一致.
     *
     * @param mixed $user
     * @param array $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * 获取最后一个尝试校验的用户
     *
     * @return Authenticatable|JWTSubject
     */
    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    /**
     * 刷新token
     *
     * @throws Error
     * @return string
     */
    public function refreshToken()
    {
        // 当前token无法正常使用时
        if (!$this->inWhitelist($this->id(), $this->getToken())) {
            throw new Error('UNAUTH', '无效的Token');
        }

        if (time() > $this->getPayload()->refresh_ttl) {
            throw new Error('UNAUTH', 'token刷新时间已过期');
        }

        $this->login($this->getUserInfo());

        return $this->getToken();
    }

    /**
     * 生成新的token
     *
     * @param JWTSubject $user
     * @param int $ttl
     * @param int $refreshTtl
     * @throws Error
     * @return string
     */
    protected function generateToken(JWTSubject $user, $ttl, $refreshTtl)
    {
        $time    = time();
        $payload = array_merge($user->getJWTCustomClaims(), [
            // 签发时间
            'iat'         => $time - 3,
            // 允许使用时间
            'nbf'         => $time - 3,
            // 过期时间
            'exp'         => $time + ($ttl * 60),
            // 刷新token的过期时间
            'refresh_ttl' => $time + $refreshTtl * 60,
        ]);

        return JWT::encode(
            $payload,
            $this->key,
            $this->alg,
        );
    }

    /**
     * 获取token中保存信息
     *
     * @return object
     */
    public function getPayload()
    {
        if (!$this->payload) {
            $token = $this->getToken();

            $this->payload = JWT::decode($token, new Key($this->key, $this->alg));
        }

        return $this->payload;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        if ($this->token !== null) {
            return $this->token;
        }

        return $this->token = $this->getTokenFromRequest();
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * 从header/input中取出token
     *
     * @return null|string
     */
    public function getTokenFromRequest()
    {
        $token = $this->request->bearerToken();

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        return $token;
    }

    /**
     * 获取用户详细信息
     *
     * @return JWTSubject|Authenticatable
     */
    public function getUserInfo()
    {
        if ($this->guest()) {
            throw new Error('UNAUTH', '用户未授权');
        }

        if ($this->userLoaded) {
            return $this->user();
        }

        $this->userLoaded = true;

        $this->user = $this->provider->retrieveById($this->id());

        return $this->user;
    }
}
