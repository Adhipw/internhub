<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreInternshipRequest;
use App\Http\Requests\HR\UpdateInternshipRequest;

use App\Models\Internship;
use App\Services\AuditService;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InternshipController extends Controller
{
    public function index()
    {
        $company = app('current_company');
        $internships = Internship::where('company_id', $company->id)
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return Inertia::render('HR/Internships/Index', [
            'internships' => $internships,
        ]);
    }

    public function create()
    {
        $industries = \Illuminate\Support\Facades\DB::table('industries')->orderBy('name')->get();

        return Inertia::render('HR/Internships/Create', [
            'industries' => $industries,
        ]);
    }

    public function store(StoreInternshipRequest $request)
    {
        $company = app('current_company');

        $internship = Internship::create(array_merge($request->validated(), [
            'company_id' => $company->id,
            'slug' => Str::slug($request->title).'-'.Str::random(5),
        ]));

        AuditService::log('internship_created', $internship);

        return redirect()->route('hr.internships.index')->with('status', 'Lowongan berhasil dibuat.');
    }

    public function edit(Internship $internship)
    {
        $this->authorize('update', $internship);

        return Inertia::render('HR/Internships/Edit', [
            'internship' => $internship,
        ]);
    }

    public function update(UpdateInternshipRequest $request, Internship $internship)
    {
        $this->authorize('update', $internship);

        $internship->update($request->validated());

        AuditService::log('internship_updated', $internship, null, $request->validated());

        return redirect()->route('hr.internships.index')->with('status', 'Lowongan berhasil diperbarui.');
    }

    public function destroy(Internship $internship)
    {
        $this->authorize('delete', $internship);

        AuditService::log('internship_deleted', $internship);

        $internship->delete();

        return redirect()->route('hr.internships.index')->with('status', 'Lowongan berhasil dihapus.');
    }
}
