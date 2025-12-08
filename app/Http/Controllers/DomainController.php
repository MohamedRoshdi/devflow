<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainController extends Controller
{
    /**
     * Store a newly created domain for a project.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'ssl_enabled' => 'boolean',
            'auto_renew_ssl' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        $validated['project_id'] = $project->id;
        $validated['status'] = 'pending';

        Domain::create($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain added successfully.');
    }

    /**
     * Update the specified domain.
     */
    public function update(Request $request, Project $project, Domain $domain): RedirectResponse
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'ssl_enabled' => 'boolean',
            'auto_renew_ssl' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        $domain->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain updated successfully.');
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Project $project, Domain $domain): RedirectResponse
    {
        $domain->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Domain deleted successfully.');
    }
}
