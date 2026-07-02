<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $exception->getMessage()], 401);
        }

        $guard = $exception->guards()[0] ?? null;

        switch ($guard) {
            case 'admin':
                $login = route('admin.login');
                break;
            case 'web':
                if ($request->is('it') || $request->is('it/*')) {
                    $login = route('it.login');
                    break;
                }

                $login = route('login');
                break;
            default:
                if ($request->is('it') || $request->is('it/*')) {
                    $login = route('it.login');
                    break;
                }

                $login = route('login');
                break;
        }

        return redirect()->guest($login);
        
    }

}


