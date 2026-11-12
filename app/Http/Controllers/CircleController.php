<?php

namespace App\Http\Controllers;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Http\Requests\Circle\DeleteCircleRequest;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleRequest;
use App\Http\Resources\ApprovalResource;
use App\Http\Resources\CircleResource;
use App\Http\Resources\UserResource;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\NewCircleAwaitingApproval;
use App\Services\Countries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class CircleController extends Controller
{
    private const int CIRCLES_PER_PAGE = 24;

    /** Requests to join and invites waiting, a page of each on the circle's page. */
    private const int WAITING_PER_PAGE = 10;

    private const int MEMBERS_PER_PAGE = 20;

    /**
     * List the approved circles, most members first, a page at a time, optionally only those whose name or description
     * contains the search (Circle::search()).
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->value();

        return Inertia::render('circles/Index', [
            'circles' => Circle::query()
                ->approved()
                ->withCount('members')
                ->when($search !== '', fn (Builder $query) => $query->search($search))
                ->orderByDesc('members_count')
                ->orderBy('name')
                ->paginate(self::CIRCLES_PER_PAGE)
                ->withQueryString()
                ->through(fn (Circle $circle): CircleResource => $circle->toResource()),
            'filters' => ['search' => $search],
            // Every circle the viewer is in, approved or not, with where its approval stands, so they can find their
            // circles while they wait. Null for guests.
            'your_circles' => $request->user()?->circles()->with('approval')->withCount('members')->orderBy('name')->get()
                ->toResourceCollection(CircleResource::class),
        ]);
    }

    /**
     * Show the page for creating a circle, or why the member can't create one yet.
     */
    public function create(): Response
    {
        $permission = Gate::inspect('create', Circle::class);

        return Inertia::render('circles/Create', [
            'cannot_create_reason' => $permission->denied() ? $permission->message() : null,
            'countries' => Countries::options(),
        ]);
    }

    /**
     * Create the circle with its creator as owner, then open it. A member's circle waits for staff approval, and
     * every staff member is emailed about it; a circle staff create is approved by them straight away.
     */
    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $creator = $request->user();
        $createdByStaff = $creator->role->internal();

        $circle = DB::transaction(function () use ($request, $creator, $createdByStaff): Circle {
            $circle = new Circle($request->safe()->only(['name', 'description']));
            $circle->locate($request->location());
            $circle->save();
            $circle->members()->attach($creator, ['role' => CircleRole::Owner]);

            $approval = Approval::open(ApprovalType::NewCircle, $circle, $creator, $creator);

            if ($createdByStaff) {
                $approval->approve($creator);
            }

            return $circle;
        });

        if (! $createdByStaff) {
            Notification::send(User::query()->staff()->get(), new NewCircleAwaitingApproval($circle, $creator));
        }

        return to_route('circles.show', $circle);
    }

    /**
     * Show the circle's page, with what the viewer can do there. Until staff approve the circle, the page also says
     * where its approval stands, which only its members and staff can see. It also shows the viewer their own request
     * to join or invite, and shows staff and the circle's owners and admins the requests and invites still waiting.
     * Those who can see the members can search them by name (User::search(), through the circle's own memberships).
     */
    public function show(Request $request, Circle $circle): Response
    {
        Gate::authorize('view', $circle);

        $viewer = $request->user();
        $membersSearch = $request->string('members_search')->value();
        $circle->loadCount('members');

        return Inertia::render('circles/Show', [
            'circle' => $circle->toResource(),
            'can' => [
                'view_channels' => Gate::allows('viewChannels', $circle),
                'request_to_join' => Gate::allows('requestToJoin', $circle),
                'invite' => Gate::allows('invite', $circle),
                'update' => Gate::allows('update', $circle),
                'delete' => Gate::allows('delete', $circle),
            ],
            'approval' => $circle->isApproved() ? null : $circle->approval->toResource(),
            'join_request' => $circle->approvalFor(ApprovalType::JoinRequest, $viewer)?->toResource(),
            'invite' => $circle->inviteFor($viewer)?->load('requester')->toResource(),
            // Each list pages on its own (its own page in the URL, the others' kept), one page either side so the
            // numbers fit in half a row, and is a closure, so reloading one list doesn't look the others up.
            'pending_join_requests' => fn () => Gate::allows('viewWaitingApprovals', $circle)
                ? $circle->waitingApprovals(ApprovalType::JoinRequest)
                    ->paginate(self::WAITING_PER_PAGE, pageName: 'requests_page')->onEachSide(1)->withQueryString()
                    ->through(fn (Approval $approval): ApprovalResource => $approval->toResource())
                : null,
            'pending_invites' => fn () => Gate::allows('viewWaitingApprovals', $circle)
                ? $circle->waitingApprovals(ApprovalType::Invite)
                    ->paginate(self::WAITING_PER_PAGE, pageName: 'invites_page')->onEachSide(1)->withQueryString()
                    ->through(fn (Approval $approval): ApprovalResource => $approval->toResource())
                : null,
            'members' => fn () => Gate::allows('viewMembers', $circle)
                ? $circle->membersByRole()
                    ->when($membersSearch !== '', fn (Builder $query) => $query->search($membersSearch))
                    ->paginate(self::MEMBERS_PER_PAGE, pageName: 'members_page')->onEachSide(1)->withQueryString()
                    ->through(fn (User $member): UserResource => $member->toResource())
                : null,
            'filters' => ['members_search' => $membersSearch],
            // For the map of where the circle is; only pages with a map get these.
            'map_tiles' => config('map.tiles'),
        ]);
    }

    /**
     * Show the page for editing the circle.
     */
    public function edit(Circle $circle): Response
    {
        Gate::authorize('update', $circle);

        return Inertia::render('circles/Edit', [
            'circle' => $circle->loadMissing('approval')->toResource(),
            'countries' => Countries::options(),
        ]);
    }

    /**
     * Save the circle's new details and location, then open it. A circle staff turned down goes back to them for
     * approval, and they're emailed again.
     */
    public function update(UpdateCircleRequest $request, Circle $circle): RedirectResponse
    {
        $circle->fill($request->safe()->only(['name', 'description']));
        $circle->locate($request->location());
        $circle->save();

        if ($circle->approval->status === ApprovalStatus::Rejected) {
            Approval::open(ApprovalType::NewCircle, $circle, $circle->approval->user, $request->user());
            Notification::send(User::query()->staff()->get(), new NewCircleAwaitingApproval($circle, $request->user(), resubmitted: true));
        }

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
