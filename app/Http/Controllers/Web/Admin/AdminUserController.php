<?php

namespace App\Http\Controllers\Web\Admin;

use App\Actions\Admin\ToggleSuperadmin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->withCount('stores')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $users->getCollection()->transform(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'stores_count' => $user->stores_count,
            'is_superadmin' => $user->isSuperadmin(),
            'has_google' => filled($user->google_id),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $search],
        ]);
    }

    public function toggleSuperadmin(Request $request, User $user, ToggleSuperadmin $toggle): RedirectResponse
    {
        // Mencabut status dari diri sendiri akan mengunci pintu dari dalam:
        // halaman ini langsung tidak bisa dibuka lagi untuk mengembalikannya.
        if ($user->is($request->user())) {
            return back()->with('error', 'Status superadmin sendiri tidak bisa dicabut dari sini.');
        }

        $promoted = $toggle->handle($user);

        return back()->with(
            'success',
            $promoted
                ? "{$user->name} sekarang superadmin."
                : "Status superadmin {$user->name} dicabut.",
        );
    }
}
