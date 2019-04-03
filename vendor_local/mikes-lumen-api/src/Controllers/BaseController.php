<?php

namespace MikesLumenApi\Controllers;

use Illuminate\Routing\Controller;
use Dingo\Api\Routing\Helpers;
use Prettus\Repository\Transformer\ModelTransformer;

class BaseController extends Controller
{
    use Helpers;

    public function created($item, $transformer = null)
    {
        if (empty($transformer)) {
            $transformer = new ModelTransformer();
        }
        return $this->response->item($item, $transformer)->setStatusCode(201);
    }


    /**
     * Magically handle calls to certain methods on the response factory.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws \ErrorException
     *
     * @return \Dingo\Api\Http\Response
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->response(), $method) || $method == 'array') {
            if ($method == 'collection' || $method == 'paginator' || $method == 'item') {
                if (count($parameters) == 1) {
                    array_push($parameters, new ModelTransformer());
                }
            }
            return call_user_func_array([$this->response(), $method], $parameters);
        }
        throw new \ErrorException('Undefined method '.get_class($this).'::'.$method);
    }

    public function getLimit($requestLimit)
    {
        return !is_null($requestLimit) ? $requestLimit : env('PAGINATION_LIMIT', 20);
    }
}
