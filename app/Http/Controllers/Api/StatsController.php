<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class StatsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return Cache::remember('stats', 3600, function () {
            $usersCount = User::count();
            $postCount = Post::count();
            $userWithNoPosts = User::whereDoesntHave('posts')->count();

            return response()->json([
                'total_users' => $usersCount,
                'total_posts' => $postCount,
                'users_with_no_posts' => $userWithNoPosts,
            ], Response::HTTP_OK);
        });
    }
}
