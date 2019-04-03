<?php

namespace MikesLumenBase\Traits;

use Illuminate\Http\Request;

trait ApiHeaderTrait
{
    public static $ROLE_ADMIN      = 1;
    public static $ROLE_SUPPORT    = 2;
    public static $ROLE_SHOP_OWNER = 11;
    public static $ROLE_SHOP_STAFF = 12;
    public static $ROLE_CUSTOMER   = 101;
    public static $ROLE_DEVICE     = 1001;

    /**
     * Get shop id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getShopId(Request $request, bool $isPrivileged = true)
    {
        $shopId = $request->header('X-ShopId');
        if (!$shopId && $isPrivileged) {
            $shopId = $request->input('shop_id');
        }
        return $shopId;
    }

    /**
     * Get shop id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getShopIdOrFail(Request $request, bool $isPrivileged = false)
    {
        $shopId = $this->getShopId($request, $isPrivileged);
        if (!$shopId && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $shopId;
    }

    /**
     * Get geust shop id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getGuestShopId(Request $request, bool $isPrivileged = true)
    {
        $shopId = $request->header('X-Guest-ShopId') ? $request->header('X-Guest-ShopId') : $request->header('X-ShopId');
        if (!$shopId && $isPrivileged) {
            $shopId = $request->input('shop_id');
        }
        return $shopId;
    }

    /**
     * Get geust shop id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getGuestShopIdOrFail(Request $request, bool $isPrivileged = false)
    {
        $shopId = $this->getGuestShopId($request, $isPrivileged);
        if (!$shopId && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $shopId;
    }

    /**
     * Get currency from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getCurrency(Request $request, bool $isPrivileged = false)
    {
        $currency = $request->header('X-Currency');
        if (!$currency && $isPrivileged) {
            $currency = $request->input('currency');
        }
        return $currency;
    }

    /**
     * Get currency from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getCurrencyOrFail(Request $request, bool $isPrivileged = false)
    {
        $currency = $this->getCurrency($request, $isPrivileged);
        if (!$currency && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $currency;
    }

    /**
     * Get user id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getUserId(Request $request, bool $isPrivileged = true)
    {
        $userId = $request->header('X-UserId');
        if (!$userId && $isPrivileged) {
            $userId = $request->input('user_id');
        }
        return $userId;
    }

    /**
     * Get user id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getUserIdOrFail(Request $request, bool $isPrivileged = false)
    {
        $userId = $this->getUserId($request);
        if (!$userId && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $userId;
    }

    /**
     * Get user id of a customer from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getCustomerUserId(Request $request, bool $isPrivileged = true)
    {
        if ($this->isCustomer($request)) {
            $userId = $request->header('X-UserId');
        } else {
            $userId = null;
        }

        if (!$userId && $isPrivileged) {
            $userId = $request->input('user_id');
        }
        return $userId;
    }

    /**
     * Get user id of a customer from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getCustomerUserIdOrFail(Request $request, bool $isPrivileged = true)
    {
        $userId = $this->getCustomerUserId($request);
        if (!$userId && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $userId;
    }

    /**
     * Get role from request headers
     *
     * @param  Request $request
     *
     * @return string
     */
    public function getRole(Request $request)
    {
        return $request->header('X-Role');
    }

    /**
     * Get role from request headers
     *
     * @param  Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getRoleOrFail(Request $request)
    {
        $role = $this->getRole($request);
        if (!$role) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $role;
    }

    /**
     * Get origin from request headers
     *
     * @param  Request $request
     *
     * @return string
     */
    public function getOrigin(Request $request)
    {
        return $request->header('X-Origin');
    }

    /**
     * Get origin from request headers
     *
     * @param  Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getOriginOrFail(Request $request)
    {
        $origin = $this->getOrigin($request);
        if (!$origin) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $origin;
    }

    /**
     * Get cart session key from request headers
     *
     * @param  Request $request
     *
     * @return string
     */
    public function getCartKey(Request $request)
    {
        return $request->header('X-CartSessionId');
    }

    /**
     * Get cart session key from request headers
     *
     * @param  Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getCartKeyOrFail(Request $request)
    {
        $cartKey = $this->getCartKey($request);
        if (!$cartKey) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $cartKey;
    }

    /**
     * Get device id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @return string
     */
    public function getDeviceId(Request $request, bool $isPrivileged = true)
    {
        $deviceId = $request->header('X-DeviceId');
        if (!$deviceId && $isPrivileged) {
            $deviceId = $request->input('device_id');
        }
        return $deviceId;
    }

    /**
     * Get device id from request headers or parameters
     *
     * @param  Request $request
     * @param  boolean $isPrivileged
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     * @return string
     */
    public function getDeviceIdOrFail(Request $request, bool $isPrivileged = false)
    {
        $deviceId = $this->getDeviceId($request);
        if (!$deviceId && !$isPrivileged) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }
        return $deviceId;
    }

    /**
     * Get Action from header
     *
     * @param Request $request
     *
     * @return string
     */
    public function getAction(Request $request)
    {
        return $request->header('X-Action');
    }

    /**
     * Get standard headers array from request
     *
     * @param  Request $request
     *
     * @return array example : ['X-ShopId' => 'f16b63843e364903b29101c0941a9e3f']
     */
    private function getStandardHeaders(Request $request)
    {
        $headers = [];
        $standardHeaderKeys = ['X-ShopId', 'X-Guest-ShopId', 'X-Currency', 'X-UserId', 'X-Role', 'X-CartSessionId', 'X-DeviceId', 'Accept-Language'];

        foreach ($standardHeaderKeys as $key) {
            if ($request->header($key)) {
                $headers[$key] = $request->header($key);
            }
        }
        return $headers;
    }

    /**
     * @param Request|null $request
     * @param string $origin
     * @return array
     */
    public function createHeadersForForwarding(Request $request, string $origin = null)
    {
        return array_merge(
            $this->getStandardHeaders($request),
            $origin !== null ? ['X-Origin' => $origin] : []
        );
    }

    /**
     * Check whether the request from authenticated user or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isLoggedIn(Request $request)
    {
        return !empty($this->getUserId($request));
    }

    /**
     * Check whether the request from authenticated device or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isDeviceLoggedIn(Request $request)
    {
        return !empty($this->getDeviceId($request));
    }

    /**
     * Check whether the request from an admin or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isAdmin(Request $request)
    {
        return $this->isLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_ADMIN;
    }

    /**
     * Check whether the request from a supporter or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isSupport(Request $request)
    {
        return $this->isLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_SUPPORT;
    }

    /**
     * Check whether the request from a shop woner or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isShopOwner(Request $request)
    {
        return $this->isLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_SHOP_OWNER;
    }

    /**
     * Check whether the request from a shop staff or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isShopStaff(Request $request)
    {
        return $this->isLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_SHOP_STAFF;
    }

    /**
     * Check whether the request from a customer or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isCustomer(Request $request)
    {
        return $this->isLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_CUSTOMER;
    }

    /**
     * Check whether the request from a device or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isDevice(Request $request)
    {
        return $this->isDeviceLoggedIn($request) && (int) $this->getRole($request) === ApiHeaderTrait::$ROLE_DEVICE;
    }

    /**
     * Check whether the request from a bulk insert or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isBulkInsert(Request $request)
    {
        return $this->getAction($request) === 'bulk-insert';
    }

    /**
     * Check whether the request from an internal service or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isInternal(Request $request)
    {
        return !empty($this->getOrigin($request));
    }

    /**
     * Check whether the request from a validation only or not
     *
     * @param  Request $request
     *
     * @return boolean
     */
    public function isValidationOnly(Request $request)
    {
        return $this->getAction($request) === 'validate';
    }
}
