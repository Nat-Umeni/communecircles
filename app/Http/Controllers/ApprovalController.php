<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalType;
use App\Http\Requests\Approval\RejectApprovalRequest;
use App\Models\Approval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    private const int APPROVALS_PER_PAGE = 24;

    /**
     * The staff queue: what's waiting for staff approval (new circles so far), longest-waiting first, with who asked.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Approval::class);

        return Inertia::render('approvals/Index', [
            'approvals' => Approval::query()
                ->pending()
                ->where('type', ApprovalType::NewCircle)
                ->with(['approvable', 'requester'])
                ->oldest('updated_at')
                ->paginate(self::APPROVALS_PER_PAGE)
                ->through(fn (Approval $approval): array => [
                    'id' => $approval->id,
                    'requested_at' => $approval->updated_at->toIso8601String(),
                    'circle' => $approval->approvable->only('public_id', 'name', 'description'),
                    'requester' => $approval->requester?->only('first_name', 'last_name'),
                ]),
        ]);
    }

    /**
     * Approve the thing waiting for approval (for an invite, accept it), then go back.
     */
    public function approve(Request $request, Approval $approval): RedirectResponse
    {
        Gate::authorize('decide', $approval);

        $approval->approve($request->user());

        Inertia::flash('status', $approval->type->approvedMessage());

        return back();
    }

    /**
     * Reject it, optionally saying why, then go back.
     */
    public function reject(RejectApprovalRequest $request, Approval $approval): RedirectResponse
    {
        $approval->reject($request->user(), $request->validated('reason'));

        Inertia::flash('status', $approval->type->rejectedMessage());

        return back();
    }
}
