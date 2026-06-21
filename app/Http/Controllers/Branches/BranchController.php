<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branches;

use App\Http\Controllers\BaseController;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends BaseController
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(): View
    {
        $branches = $this->branchService->all();
        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'code'    => ['required', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:150'],
            'is_head' => ['boolean'],
        ]);

        $branch = $this->branchService->create($data);
        $this->success('Branch created successfully.');
        return redirect()->route('branches.show', $branch->id);
    }

    public function show(int $id): View
    {
        $branch = \App\Models\Branch::findOrFail($id);
        return view('branches.show', compact('branch'));
    }

    public function edit(int $id): View
    {
        $branch = \App\Models\Branch::findOrFail($id);
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'code'    => ['required', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'email'   => ['nullable', 'email', 'max:150'],
        ]);

        $this->branchService->update($id, $data);
        $this->success('Branch updated successfully.');
        return redirect()->route('branches.show', $id);
    }

    public function destroy(int $id): RedirectResponse
    {
        $branch = \App\Models\Branch::findOrFail($id);
        $branch->delete();
        $this->success('Branch deleted.');
        return redirect()->route('branches.index');
    }

    public function enable(int $id): RedirectResponse
    {
        $this->branchService->enable($id);
        $this->success('Branch enabled.');
        return back();
    }

    public function disable(int $id): RedirectResponse
    {
        $this->branchService->disable($id);
        $this->success('Branch disabled.');
        return back();
    }
}
