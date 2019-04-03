<?php

namespace MikesLumenApi\Providers;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\Response;

class ExceptionServiceProvider extends ServiceProvider
{

    /**
     * Translate multi language for API message
     *
     * @param string $key
     * @param array $params
     * @param string $domain
     * @param string $locale
     *
     * @return string
     */
    private function trans($key, $params = [], $domain = 'messages', $locale = null)
    {
        return app('translator')->trans($key, $params, $domain, $locale);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $handler = app('Dingo\Api\Exception\Handler');

        $handler->register(function (\Prettus\Validator\Exceptions\ValidatorException $e) {
            \Log::info(print_r($e->toArray(), true));

            $errors = [];
            foreach ($e->getMessageBag()->getMessages() as $field => $message) {
                $errors[] = [
                    'field' => $field,
                    'message' => implode($message)
                ];
            }
            $response['message'] = $this->trans('mikelumenapi::message.validation_failed');
            $response['errors'] = $errors;
            $response['code'] = 'repository.validation_failed';
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        });

        $handler->register(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $response['message'] = $this->trans('mikelumenapi::message.model_not_found');
            $response['code'] = 'database.model_not_found';
            return response()->json($response, Response::HTTP_NOT_FOUND);
        });

        $handler->register(function (\Illuminate\Database\QueryException $e) {
            $response['message'] = $this->trans('mikelumenapi::message.query_exception');
            $response['code'] = 'database.query_exception';
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $handler->register(function (\PDOException $e) {
            $response['message'] = $this->trans('mikelumenapi::message.pdo_exception');
            $response['code'] = 'database.pdo_exception';
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $handler->register(function (\MikesLumenApi\Exceptions\AppException $e) {
            $response['message'] = $e->getMessage();
            $response['code'] = $e->getErrorCode();
            return response()->json($response, Response::HTTP_INTERNAL_SERVER_ERROR);
        });

        $handler->register(function (\MikesLumenApi\Exceptions\RequestException $e) {
            $response['message'] = $e->getMessage();
            $response['code'] = $e->getErrorCode();
            return response()->json($response, Response::HTTP_BAD_REQUEST);
        });
    }
}
