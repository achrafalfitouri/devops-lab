<?php
 
namespace App\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 
 
class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions) 
    {
        $authUser = Auth::user();
 
        if (!$authUser) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
 
        $userId = $authUser->id;
 
        $user = User::find($userId);
 
        if (!$user) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
 
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request); 
            }
        }
 
        return response()->json(['message' => 'Forbidden'], 403);
    }
}