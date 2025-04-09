<?php

namespace Deep\FormTool\Core;

use Illuminate\Http\Request;

trait ApiResponses
{
    protected $version = '1.0.0';

    protected $isIncludeSettings = false;

    private $firstRequest;
    private $firstRequestFiles;

    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->firstRequest = clone $request;
            $this->firstRequestFiles = $_FILES;

            return $next($request);
        });
    }

    protected function getCommonResponse($message, $status, $data = [])
    {
        return [
            'version' => $this->version,
            'message' => $message,
            'status' => $status,
            'data' => is_array($data) ? (object) $data : $data,
        ];
    }

    protected function success($data = [], $message = '')
    {
        if (! $data) {
            $data = [];
        }

        if ($this->isIncludeSettings) {
            $data['settings'] = app('settings');
        }

        return $this->sendResponse($this->getCommonResponse($message, true, $data), \Illuminate\Http\Response::HTTP_OK);
    }

    protected function failed($message = '', $data = [])
    {
        return $this->sendResponse($this->getCommonResponse($message, false, $data), \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function notFound($message = '404 not found!', $data = [])
    {
        return $this->sendResponse($this->getCommonResponse($message, false, $data), \Illuminate\Http\Response::HTTP_NOT_FOUND);
    }

    protected function validationError($validator, $message = '')
    {
        if (! $validator instanceof \Illuminate\Validation\Validator) {
            throw new \Exception('$validator in not an instance of '.\Illuminate\Validation\Validator::class);
        }

        $data = [];
        $errors = $validator?->messages()->toArray() ?? [];
        foreach ($errors as $key => $row) {
            $data[$key] = $row[0] ?? '';
        }

        if (! $message) {
            $message = $validator?->messages()->first();
        }

        $response = array_merge($this->getCommonResponse($message, false), [
            'errors' => (object) $data,
        ]);

        return $this->sendResponse($response, \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function authError($message = '')
    {
        return $this->sendResponse($this->getCommonResponse($message, false), \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    protected function serverError($message)
    {
        if (app()->isProduction()) {
            $message = '';
        }

        return $this->sendResponse($this->getCommonResponse($message, false), \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    protected function sendResponse($response, $statusCode)
    {
        return response()->json($response, $statusCode);
    }
}
