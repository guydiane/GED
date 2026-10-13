<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Liste de tous les documents (avec recherche par titre)
    public function index(Request $request)
    {
        $documents = Document::with('user')
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest()
            ->get();

        return view('documents.index', compact('documents'));
    }

    // Formulaire d'upload
    public function create()
    {
        return view('documents.create');
    }

    // Enregistre un nouveau document
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|max:5120|mimes:pdf,doc,docx,jpg,png',
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $request->user()->documents()->create([
            'title' => $request->title,
            'path'  => $path,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document ajouté.');
    }

    // Affiche un document
    public function show(Document $document)
    {
        return view('documents.show', compact('document'));
    }

    // Formulaire de modification
    public function edit(Document $document)
    {
        $this->authorizeOwner($document);

        return view('documents.edit', compact('document'));
    }

    // Enregistre la modification
    public function update(Request $request, Document $document)
    {
        $this->authorizeOwner($document);

        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'nullable|file|max:5120|mimes:pdf,doc,docx,jpg,png',
        ]);

        $data = ['title' => $request->title];

        // Si un nouveau fichier est envoyé, on remplace l'ancien
        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->path);
            $data['path'] = $request->file('file')->store('documents', 'public');
        }

        $document->update($data);

        return redirect()->route('documents.index')->with('success', 'Document mis à jour.');
    }

    // Supprime un document et son fichier
    public function destroy(Document $document)
    {
        $this->authorizeOwner($document);

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document supprimé.');
    }

    // Seul le propriétaire peut modifier ou supprimer
    private function authorizeOwner(Document $document): void
    {
        abort_unless($document->user_id === auth()->id(), 403);
    }
}