<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Liste de tous les utilisateurs
    public function index()
    {
        $users = User::latest()->get();

        return view('users.index', compact('users'));
    }

    // Affiche le formulaire de création
    public function create()
    {
        return view('users.create');
    }

    // Enregistre un nouvel utilisateur
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }

    // Affiche un utilisateur
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    // Affiche le formulaire de modification
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    // Enregistre la modification
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Si le mot de passe est vide, on ne le change pas
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    // Supprime un utilisateur
    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte ici.');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé.');
    }
}