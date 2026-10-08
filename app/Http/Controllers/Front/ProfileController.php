<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** MODULE UTILISATEURS — profil de l'utilisateur connecté. */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('front.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'regex:/^[0-9+\s]{8,15}$/'],
            'city' => ['nullable', 'string', 'max:80'],
        ], [
            'phone.regex' => 'Numéro de téléphone invalide.',
        ], ['name' => 'nom', 'email' => 'e-mail', 'phone' => 'téléphone', 'city' => 'ville']);

        $user->update($data);

        return back()->with('success', 'Profil mis à jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ], ['current_password' => 'mot de passe actuel', 'password' => 'nouveau mot de passe']);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Mot de passe modifié.');
    }
}
