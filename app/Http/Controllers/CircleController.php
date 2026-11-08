<?php

namespace App\Http\Controllers;

use App\Enums\CircleRole;
use App\Http\Requests\Circle\DeleteCircleRequest;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleRequest;
use App\Models\Circle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    private const int CIRCLES_PER_PAGE = 24;

    /**
     * List the circles in name order, a page at a time, optionally only those whose name contains the search.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('circles/Index', [
            'circles' => Circle::query()
                ->withCount('members')
                ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
                ->orderBy('name')
                ->paginate(self::CIRCLES_PER_PAGE)
                ->withQueryString()
                ->through(fn (Circle $circle): array => $circle->only('public_id', 'name', 'description', 'members_count')),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show the page for creating a circle.
     */
    public function create(): Response
    {
        Gate::authorize('create', Circle::class);

        return Inertia::render('circles/Create');
    }

    /**
     * Create the circle with its creator as owner, then open it.
     */
    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $circle = DB::transaction(function () use ($request): Circle {
            $circle = Circle::create($request->validated());
            $circle->members()->attach($request->user(), ['role' => CircleRole::Owner]);

            return $circle;
        });

        return to_route('circles.show', $circle);
    }

    /**
     * Show the circle's public page, with what the viewer can do there.
     */
    public function show(Circle $circle): Response
    {
        return Inertia::render('circles/Show', [
            'circle' => $circle->loadCount('members')->only('public_id', 'name', 'description', 'members_count'),
            'can' => [
                'view_channels' => Gate::allows('viewChannels', $circle),
                'request_to_join' => Gate::allows('requestToJoin', $circle),
                'update' => Gate::allows('update', $circle),
                'delete' => Gate::allows('delete', $circle),
            ],
        ]);
    }

    /**
     * Show the page for editing the circle.
     */
    public function edit(Circle $circle): Response
    {
        Gate::authorize('update', $circle);

        return Inertia::render('circles/Edit', [
            'circle' => $circle->only('public_id', 'name', 'description'),
        ]);
    }

    /**
     * Save the circle's new details, then open it.
     */
    public function update(UpdateCircleRequest $request, Circle $circle): RedirectResponse
    {
        $circle->update($request->validated());

        return to_route('circles.show', $circle);
    }

    /**
     * Delete the circle, then go back to the list.
     */
    public function destroy(DeleteCircleRequest $request, Circle $circle): RedirectResponse
    {
        $circle->delete();

        return to_route('circles.index');
    }
}
