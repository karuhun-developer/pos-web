<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\Web\LoginWebSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LoginWebRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'googleEnabled' => filled(config('services.google.client_secret')),
        ]);
    }

    public function store(LoginWebRequest $request, LoginWebSession $action): RedirectResponse
    {
        $action->handle(
            $request,
            $request->validated('email'),
            $request->validated('password'),
            (bool) $request->validated('remember', false),
        );

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
