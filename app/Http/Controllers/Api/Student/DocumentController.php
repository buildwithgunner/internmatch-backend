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
        $path         = $file->storeAs('documents', $filename, 'local');

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
            'url'      => route('documents.serve', ['id' => $document->id]),
        ], 201);
    }

    public function index(Request $request)
    {
        $documents = $request->user()->documents()->get();

        return response()->json([
            'documents' => $documents->map(fn ($doc) => [
                'type'          => $doc->type,
                'original_name' => $doc->original_name,
                'url'           => route('documents.serve', ['id' => $doc->id]),
            ]),
        ]);
    }

    public function destroy(Request $request, $type)
    {
        $document = $request->user()->documents()->where('type', $type)->firstOrFail();

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Document deleted']);
    }

    /**
     * Serve a protected document.
     */
    public function serve(Request $request, $id)
    {
        $user = $request->user();
        $document = Document::findOrFail($id);

        // Authorization: Only the owner, their recruiter, their company, or an admin can view.
        $isOwner = $user instanceof \App\Models\User && $user->id === $document->user_id;
        $isAdmin = $user instanceof \App\Models\Admin;
        
        // For companies and recruiters, they can view if the student has applied to one of their internships
        $isAuthorized = $isOwner || $isAdmin;

        if (!$isAuthorized) {
            if ($user instanceof \App\Models\Recruiter || $user instanceof \App\Models\Company) {
                $hasApplication = \App\Models\Application::where('student_id', $document->user_id)
                    ->whereHas('internship', function ($q) use ($user) {
                        if ($user instanceof \App\Models\Recruiter) {
                            $q->where('recruiter_id', $user->id);
                        } else {
                            $q->whereHas('recruiter', fn($rq) => $rq->where('company_id', $user->id));
                        }
                    })->exists();
                
                if ($hasApplication) {
                    $isAuthorized = true;
                }
            }
        }

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized access to document.'], 403);
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk('local')->response($document->file_path, $document->original_name);
    }
}
