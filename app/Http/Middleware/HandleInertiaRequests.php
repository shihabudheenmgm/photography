<?php

// namespace App\Http\Middleware;

// use Illuminate\Foundation\Inspiring;
// use Illuminate\Http\Request;
// use Inertia\Middleware;
// use Tighten\Ziggy\Ziggy;

// class HandleInertiaRequests extends Middleware
// {
//     /**
//      * The root template that's loaded on the first page visit.
//      *
//      * @see https://inertiajs.com/server-side-setup#root-template
//      *
//      * @var string
//      */
//     protected $rootView = 'app';

//     /**
//      * Determines the current asset version.
//      *
//      * @see https://inertiajs.com/asset-versioning
//      */
//     public function version(Request $request): ?string
//     {
//         return parent::version($request);
//     }

//     /**
//      * Define the props that are shared by default.
//      *
//      * @see https://inertiajs.com/shared-data
//      *
//      * @return array<string, mixed>
//      */
//     public function share(Request $request): array
//     {
//         [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

//         return [
//             ...parent::share($request),
//             'name' => config('app.name'),
//             'quote' => ['message' => trim($message), 'author' => trim($author)],
//             'auth' => [
//                 'user' => $request->user(),
//             ],
//             'ziggy' => fn (): array => [
//                 ...(new Ziggy)->toArray(),
//                 'location' => $request->url(),
//             ],
//             'sidebarOpen' => $request->cookie('sidebar_state') === 'true',

//             'status' => fn () => $request->session()->get('status'),
            
//             'success' => fn () => $request->session()->get('success'),
//         ];
//     }
// }


namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'email_verified_at' => $request->user()->email_verified_at,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            
            'routes' => fn () => $this->getSafeRoutes($request),
            'authRoutes' => fn () => $this->getAuthRoutes($request),
            
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
        ]);
    }

    protected function getSafeRoutes(Request $request): array
    {
        return [
            'home' => route('home'),
            'login' => route('login'),
            'register' => route('register'),
            'galleries_index' => route('galleries.index'),
            'galleries_show' => route('galleries.show', ['id' => ':id']),
        ];
    }

    protected function getAuthRoutes(Request $request): array
    {
        if (!$request->user()) {
            return [];
        }

        return [
            'dashboard' => route('dashboard'),
            'profile' => route('profile'),
            'logout' => route('logout'),
        ];
    }

    public function rootView(Request $request): string
    {
        if (app()->environment('production')) {

        }
        
        return parent::rootView($request);
    }
}
