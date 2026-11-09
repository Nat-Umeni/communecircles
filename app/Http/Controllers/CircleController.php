<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Http\Requests\Circle\DeleteCircleRequest;
use App\Http\Requests\Circle\StoreCircleRequest;
use App\Http\Requests\Circle\UpdateCircleRequest;
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

    /**
     * List the approved circles in name order, a page at a time, optionally only those whose name contains the search.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        return Inertia::render('circles/Index', [
            'circles' => Circle::query()
                ->approved()
                ->withCount('members')
                ->when($search !== '', fn (Builder $query) => $query->whereLike('name', "%{$search}%"))
                ->orderBy('name')
                ->paginate(self::CIRCLES_PER_PAGE)
                ->withQueryString()
                ->through(fn (Circle $circle): array => [...$this->details($circle), 'members_count' => $circle->members_count]),
            'filters' => ['search' => $search],
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
     * to join or invite, and shows the circle's owners and admins the requests and invites still waiting.
     */
    public function show(Request $request, Circle $circle): Response
    {
        Gate::authorize('view', $circle);

        return Inertia::render('circles/Show', [
            'circle' => [...$this->details($circle), 'members_count' => $circle->loadCount('members')->members_count],
            'can' => [
                'view_channels' => Gate::allows('viewChannels', $circle),
                'request_to_join' => Gate::allows('requestToJoin', $circle),
                'update' => Gate::allows('update', $circle),
                'delete' => Gate::allows('delete', $circle),
            ],
            'approval' => $circle->isApproved() ? null : [
                'id' => $circle->approval->id,
                'status' => $circle->approval->status->value,
                'label' => $circle->approval->status->label(),
                'reason' => $circle->approval->reason,
                'can_decide' => Gate::allows('decide', $circle->approval),
            ],
            'join_request' => $this->joinRequestOf($circle, $request->user()),
            'pending_join_requests' => $this->waitingForManagers($circle, $request->user(), ApprovalType::JoinRequest, 'requested_at'),
            'invite' => $this->inviteFor($circle, $request->user()),
            'pending_invites' => $this->waitingForManagers($circle, $request->user(), ApprovalType::Invite, 'invited_at'),
        ]);
    }

    /**
     * The viewer's own invite into the circle, still waiting for them to accept or decline, with who sent it; null
     * when they have none.
     *
     * @return array{id: int, invited_by: string|null}|null
     */
    private function inviteFor(Circle $circle, ?User $viewer): ?array
    {
        $invite = $viewer === null ? null : $circle->approvals()
            ->pending()
            ->where('type', ApprovalType::Invite)
            ->where('user_id', $viewer->id)
            ->with('requester')
            ->first();

        return $invite === null ? null : [
            'id' => $invite->id,
            'invited_by' => $invite->requester === null ? null : "{$invite->requester->first_name} {$invite->requester->last_name}",
        ];
    }

    /**
     * The viewer's own request to join, with where it stands, or null when they haven't asked.
     *
     * @return array{status: string, label: string, reason: string|null}|null
     */
    private function joinRequestOf(Circle $circle, ?User $viewer): ?array
    {
        $request = $viewer === null ? null : $circle->approvals()
            ->where('type', ApprovalType::JoinRequest)
            ->where('user_id', $viewer->id)
            ->first();

        return $request === null ? null : [
            'status' => $request->status->value,
            'label' => $request->status->label(),
            'reason' => $request->reason,
        ];
    }

    /**
     * The approvals of a type still waiting (requests to join, or invites), oldest first, each with the person it's
     * about and since when (under $sinceKey). Only the circle's owners and admins get the list; anyone else, staff
     * included, gets null, since who joins a circle is the circle's own business.
     *
     * @return list<array<string, int|string>>|null
     */
    private function waitingForManagers(Circle $circle, ?User $viewer, ApprovalType $type, string $sinceKey): ?array
    {
        if ($viewer === null || ! ($circle->membershipOf($viewer)?->role->hasManagerRole() ?? false)) {
            return null;
        }

        return $circle->approvals()
            ->pending()
            ->where('type', $type)
            ->with('user')
            ->oldest('updated_at')
            ->get()
            // TODO: add each person's profile link once profiles exist, so owners and admins can check them first.
            ->map(fn (Approval $approval): array => [
                'id' => $approval->id,
                'name' => "{$approval->user->first_name} {$approval->user->last_name}",
                $sinceKey => $approval->updated_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Show the page for editing the circle.
     */
    public function edit(Circle $circle): Response
    {
        Gate::authorize('update', $circle);

        return Inertia::render('circles/Edit', [
            'circle' => $this->details($circle),
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

    /**
     * What the pages show about a circle, with its location (or null) for the place name and the explore map.
     *
     * @return array{public_id: string, name: string, description: string, location: array<string, mixed>|null}
     */
    private function details(Circle $circle): array
    {
        return [
            ...$circle->only('public_id', 'name', 'description'),
            'location' => $circle->location()?->toArray(),
        ];
    }
}
