<?php

namespace App\Exceptions;

use Exception;

class UserLimitExceededException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = 'User limit exceeded for your subscription plan', int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'error' => 'user_limit_exceeded',
            ], $this->getCode());
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['user_limit' => $this->getMessage()]);
    }
}