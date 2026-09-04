<?php

namespace App\Http\Controllers\Communications;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamMemberAuthController extends Controller
{
    public function create(): View
    {
        return view('auth.team-member-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('team_member')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Credenciales incorrectas.'])->onlyInput('email');
        }

        $actor = Auth::guard('team_member')->user();

        if (! $actor->is_active) {
            Auth::guard('team_member')->logout();

            return back()->withErrors(['email' => 'Esta cuenta está desactivada.'])->onlyInput('email');
        }

        // Los guards "web" y "team_member" son accesos mutuamente excluyentes: si el mismo
        // navegador ya tenía una sesión de contador activa, hay que cerrarla — de lo contrario
        // ambas quedan autenticadas a la vez y las vistas que priorizan `@auth('web')`
        // (comunicaciones-layout, etc.) terminan mostrando el CRM completo en vez del chat.
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->regenerate();

        return redirect()->route('communications.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('team_member')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('team-member.login');
    }
}
