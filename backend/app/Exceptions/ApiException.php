<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;


class ApiException extends Exception
{
    protected $errors;
    protected $statusCode;

    public function __construct(int $code = 422, string $message = "Validation failed", array $errors = [])
    {
        parent::__construct($message, $code);

        $this->statusCode = $code;
        $this->errors = $errors;
    }

    public function render(): JsonResponse
    {
        $data = [
            'code' => $this->statusCode,
            'message' => $this->getMessage(),
        ];

        if (count($this->errors) > 0) {
            $data['content'] = $this->errors;
        }

        return response()->json($data, $this->statusCode);
    }
}
