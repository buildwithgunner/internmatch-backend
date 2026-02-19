<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'type' => 'required|in:resume,cover_letter,student_id,transcript,primary_certificate,secondary_certificate,university_certificate,certificate,recommendation_letter,passport_photo',
        ]);

        $user = $request->user();

        $file         = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $filename     = Str::random(40) . '.' . $file->getClientOriginalExtension();
        $path         = $file->storeAs('documents', $filename, 'public');

        // Delete old document of same type (one per type)
        Document::where('user_id', $user->id)->where('type', $request->type)->delete();

        $document = Document::create([
            'user_id'       => $user->id,
            'type'          => $request->type,
            'file_path'     => $path,
            'original_name' => $originalName,
        ]);

        return response()->json([
            'message'  => 'Document uploaded successfully',
            'document' => $document,
            'url'      => asset('storage/' . $path),
        ], 201);
    }

    public function index(Request $request)
    {
        $documents = $request->user()->documents()->get();

        return response()->json([
            'documents' => $documents->map(fn ($doc) => [
                'type'          => $doc->type,
                'original_name' => $doc->original_name,
                'url'           => asset('storage/' . $doc->file_path),
            ]),
        ]);
    }

    public function destroy(Request $request, $type)
    {
        $document = $request->user()->documents()->where('type', $type)->firstOrFail();

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted']);
    }
}
