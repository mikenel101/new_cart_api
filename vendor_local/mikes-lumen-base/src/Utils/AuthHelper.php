<?php

namespace MikesLumenBase\Utils;

use Illuminate\Http\Request;
use MikesLumenBase\Traits\ApiHeaderTrait;

class AuthHelper
{
    use ApiHeaderTrait;

    private const CONFIG_FOLDER_NAME = 'config';
    private const AUTHORITY_FILE_NAME = 'authority.json';
    private const SPECIAL_AUTHORITY = 'SpecialAuthority';

    // authority codes
    private const CODE_AUTHORIZED = 'authorized';
    private const CODE_AUTH_TYPE_ONLY = 'auth_type_only';
    private const CODE_PUBLIC_ONLY = 'public_only';
    private const CODE_SHOP_ONLY = 'shop_only';
    private const CODE_USER_ONLY = 'user_only';
    private const VALID_CODES = array(self::CODE_AUTHORIZED, self::CODE_AUTH_TYPE_ONLY, self::CODE_PUBLIC_ONLY, self::CODE_SHOP_ONLY, self::CODE_USER_ONLY);

    // scope method name for AuthCriteria
    const SCOPE_METHOD_ISUSER = 'scopeOwnedBy';
    const SCOPE_METHOD_ISSHOP = 'scopeInShop';
    const SCOPE_METHOD_ISPUBLIC = 'scopePublic';
    const SCOPE_METHOD_ISAUTHTYPE = 'scopeEditable';

    protected $request;
    public $authorities;
    public $specialAuthorities;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->authorities = $this->getAuthorities();
        $this->specialAuthorities = $this->getSpecialAuthorities();
    }

    private function buildConfigFilePath()
    {
        return app()->basePath() . '/' . self::CONFIG_FOLDER_NAME . '/' . self::AUTHORITY_FILE_NAME;
    }

    public function getUnauthorizedMessage()
    {
        return trans('mikelumenapi::message.unauthorized');
    }

    public function getAuthorities()
    {
        return json_decode(file_get_contents($this->buildConfigFilePath()), true);
    }

    public function getControllerName()
    {
        return explode('@', $this->request->route()[1]['controller'])[0];
    }

    public function getMethodName()
    {
        return explode('@', $this->request->route()[1]['controller'])[1];
    }

    public function getRoute()
    {
        return $this->formatURL(explode('@', $this->request->route()[1]['uri'])[0]);
    }

    public function getRequestMethodLowerCase()
    {
        return strtolower($this->request->method());
    }

    public function getRequestRole()
    {
        return $this->getRole($this->request);
    }

    public function getRequstShopId()
    {
        return $this->getShopId($this->request);
    }

    public function getRequstUserId()
    {
        return $this->getUserId($this->request);
    }

    public function getSpecialAuthorities()
    {
        $authorities = $this->authorities;
        $specialAuthorities = array();
        foreach ($authorities as $role => $item) {
            if (array_key_exists(self::SPECIAL_AUTHORITY, $authorities[$role])) {
                $specialAuthorities[$role] = $authorities[$role][self::SPECIAL_AUTHORITY];
            }
        }

        return $specialAuthorities;
    }

    public function hasSpecialAuthority($role, $specialAuthName)
    {
        return in_array($specialAuthName, $this->specialAuthorities[$role][self::SPECIAL_AUTHORITY]);
    }

    public function formatURL($url)
    {
        $ch = '/';
        if ($url[0] === $ch) {
            $url = substr($url, 1);
        }

        if (substr($url, -1) === $ch) {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    public function getSpecifiedAuthArray($role, $route, $method)
    {
        return $this->authorities[$role][$route][$method];
    }

    public function hasAuthority($role, $route, $method, $authName)
    {
        return in_array($authName, $this->getSpecifiedAuthArray($role, $route, $method));
    }

    public function isUserOnly($role, $route, $method)
    {
        return $this->hasAuthority($role, $route, $method, self::CODE_USER_ONLY);
    }

    public function isShopOnly($role, $route, $method)
    {
        return $this->hasAuthority($role, $route, $method, self::CODE_SHOP_ONLY);
    }

    public function isPublicOnly($role, $route, $method)
    {
        return $this->hasAuthority($role, $route, $method, self::CODE_PUBLIC_ONLY);
    }

    public function isAuthTypeOnly($role, $route, $method)
    {
        return $this->hasAuthority($role, $route, $method, self::CODE_AUTH_TYPE_ONLY);
    }

    public function isAuthorized($role, $route, $method)
    {
        // check if authority of the route exist
        if (!array_key_exists($route, $this->authorities[$role])) {
            return false;
        }
        // check if authority of the method exist
        if (!array_key_exists($method, $this->authorities[$role][$route])) {
            return false;
        }

        // check if specified authority array valid
        $authArray = $this->getSpecifiedAuthArray($role, $route, $method);
        return $this->checkIfAuthArrayValid($authArray);
    }

    private function checkIfAuthValid($auth)
    {
        return in_array($auth, self::VALID_CODES);
    }

    private function checkIfAuthArrayValid($authArray)
    {
        if (empty($authArray)) {
            return false;
        } else {
            foreach ($authArray as $auth) {
                // check if code in Auth Array is valid
                if (!$this->checkIfAuthValid($auth)) {
                    return false;
                }
            }
        }

        return true;
    }
}
