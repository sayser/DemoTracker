<?php

declare(strict_types=1);

final class Http
{
    public static function notFound(): never
    {
        http_response_code(404);
        $user = Auth::user();
        render('error', [
            'user' => $user,
            'title' => 'Not found',
            'message' => 'That page does not exist.',
        ]);
        exit;
    }

    public static function methodNotAllowed(): never
    {
        http_response_code(405);
        echo 'Method not allowed';
        exit;
    }
}
