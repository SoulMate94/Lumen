<?php

// Migrate user api filter

===================Middleware\Filter\MigreateUser.php==========================

namespace App\Http\Middleware\Filter;

use Closure;

class MigrateUser
{
    public function __construct()
    {
        try{
        } catch (\Exception $e) {
        } finally {
        }
    }

    public function handle($request, Closure $next)
    {
        if (!($id = ($request->route()[2]['id'] ?? false))
            || !is_numeric($id)
            || (1 > $id)
        ) {
            return response()->json([
                'error' => 'Illegal user id `'.$id.'`.',
            ], 403);
        }

        $request->attributes->add([
            'id' => intval($id)
        ]);

        return $next($request);
    }
}