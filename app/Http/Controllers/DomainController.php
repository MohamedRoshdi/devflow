<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class DomainController extends Controller
{
    /**
     * Store a newly created domain for a project.
     */
    public function store(StoreDomainRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();

        $validated['project_id'] = $project->id;
        $validated['status'] = 'pending';

        Domain::create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain added successfully.');
    }

    /**
     * Update the specified domain.
     */
    public function update(UpdateDomainRequest $request, Project $project, Domain $domain): RedirectResponse
    {
        $this->authorize('update', $domain);

        $validated = $request->validated();

        $domain->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain updated successfully.');
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Project $project, Domain $domain): RedirectResponse
    {
        $this->authorize('delete', $domain);

        $domain->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain deleted successfully.');
    }
}
