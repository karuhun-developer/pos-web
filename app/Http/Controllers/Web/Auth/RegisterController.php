<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\Web\RegisterWebUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\RegisterWebRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'googleEnabled' => filled(config('services.google.client_secret')),
        ]);
    }

    public function store(RegisterWebRequest $request, RegisterWebUser $action): RedirectResponse
    {
        $action->handle(
            $request,
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return redirect()->route('dashboard');
    }
}
