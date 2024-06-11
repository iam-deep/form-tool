<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Support\Settings;

trait ApiResponses
{
    protected $version = '1.0.0';

    protected $isIncludeSettings = false;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            Settings::init();

            return $next($request);
        });
    }

    protected function getCommonResponse($message, $status)
    {
        return [
            'version' => $this->version,
            'message' => $message,
            'status' => $status,
            'data' => (object) [],
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

        return response()->json(array_merge($this->getCommonResponse($message, true), [
            'data' => is_array($data) ? (object) $data : $data
        ]), \Illuminate\Http\Response::HTTP_OK);
    }

    protected function failed($message = '', $data = [])
    {
        return response()->json(array_merge($this->getCommonResponse($message, false), [
            'data' => is_array($data) ? (object) $data : $data
        ]), \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function notFound($message = '404 not found!', $data = [])
    {
        return response()->json(array_merge($this->getCommonResponse($message, false), [
            'data' => is_array($data) ? (object) $data : $data
        ]), \Illuminate\Http\Response::HTTP_NOT_FOUND);
    }

    protected function validationError($validator, $message = '')
    {
        if (! $validator instanceof \Illuminate\Validation\Validator) {
            throw new \Exception('$validator in not an instance of '.\Illuminate\Validation\Validator::class);
        }

        $data = [];
        $errors = $validator->messages()->toArray();
        foreach ($errors as $key => $row) {
            $data[$key] = $row[0] ?? '';
        }

        if (! $message) {
            $message = $validator->messages()->first();
        }

        return response()->json(array_merge($this->getCommonResponse($message, false), [
            'errors' => (object) $data
        ]), \Illuminate\Http\Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function authError($message = '')
    {
        return response()->json($this->getCommonResponse($message, false), \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
    }

    protected function serverError($message)
    {
        if (app()->isProduction()) {
            $message = '';
        }

        return response()->json($this->getCommonResponse($message, false), \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
