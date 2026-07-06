<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Models\OnboardingDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApiOnboardingController extends ApiBaseController
{
    /**
     * Get onboarding documents for an application.
     */
    public function index(Application $application): JsonResponse
    {
        $this->authorizeAccess($application);

        $documents = OnboardingDocument::where('application_id', $application->id)
            ->with('verifier')
            ->get();

        return $this->sendResponse($documents, 'Onboarding documents retrieved');
    }

    /**
     * Upload a document (Student).
     */
    public function upload(Request $request, Application $application): JsonResponse
    {
        if ($application->user_id !== Auth::id()) {
            return $this->sendError('Unauthorized', [], 403);
        }

        if ($application->status !== 'accepted') {
            return $this->sendError('You can only upload onboarding documents after being accepted.', [], 422);
        }

        $request->validate([
            'type' => 'required|string|in:agreement,ktp,campus_letter,other',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB limit
        ]);

        $file = $request->file('file');
        $path = $file->store("onboarding/{$application->id}");

        $document = OnboardingDocument::updateOrCreate(
            ['application_id' => $application->id, 'type' => $request->type],
            [
                'user_id' => Auth::id(),
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'status' => OnboardingDocument::STATUS_PENDING,
                'notes' => null,
                'verified_by' => null,
                'verified_at' => null,
            ]
        );

        return $this->sendResponse($document, 'Document uploaded successfully', 201);
    }

    /**
     * Verify or reject a document (HR/Admin).
     */
    public function verify(Request $request, OnboardingDocument $document): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $application = $document->application;

        // Check if user is staff of the company
        $isCompanyStaff = $user->companies()->where('companies.id', $application->internship->company_id)->exists();

        if (! $isCompanyStaff && ! $user->hasRole('admin')) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $request->validate([
            'status' => 'required|string|in:verified,rejected',
            'notes' => 'required_if:status,rejected|nullable|string|max:500',
        ]);

        $document->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'verified_by' => Auth::id(),
            'verified_at' => $request->status === 'verified' ? now() : null,
        ]);

        return $this->sendResponse($document, "Document status updated to {$request->status}");
    }

    /**
     * Download document.
     */
    public function download(OnboardingDocument $document)
    {
        $this->authorizeAccess($document->application);

        if (! Storage::exists($document->file_path)) {
            return abort(404);
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    /**
     * Internal access control.
     */
    private function authorizeAccess(Application $application)
    {
        /** @var User $user */
        $user = Auth::user();

        $isOwner = $application->user_id === $user->id;
        $isStaff = $user->companies()->where('companies.id', $application->internship->company_id)->exists();
        $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');

        if (! $isOwner && ! $isStaff && ! $isAdmin) {
            abort(403, 'Unauthorized access to onboarding documents.');
        }
    }
}
