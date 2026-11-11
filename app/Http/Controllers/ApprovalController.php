<?php

namespace App\Http\Controllers;

use App\Enums\Approval\ApprovalType;
use App\Http\Requests\Approval\RejectApprovalRequest;
use App\Http\Resources\ApprovalResource;
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
     * The staff queue: every kind of approval staff decide (ApprovalType::forStaff()) still waiting, the longest-waiting
     * first, with who asked.
     */
    public function index(): Response
    {
        Gate::authorize('viewAny', Approval::class);

        return Inertia::render('approvals/Index', [
            'approvals' => Approval::query()
                ->pending()
                ->whereIn('type', ApprovalType::forStaff())
                ->with(['approvable', 'requester'])
                ->oldest('updated_at')
                ->paginate(self::APPROVALS_PER_PAGE)
                ->through(fn (Approval $approval): ApprovalResource => $approval->toResource()),
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

    /**
     * Take it back while it's still waiting (only invites, so far), then go back.
     */
    public function cancel(Request $request, Approval $approval): RedirectResponse
    {
        Gate::authorize('cancel', $approval);

        $approval->cancel($request->user());

        Inertia::flash('status', $approval->type->cancelledMessage());

        return back();
    }
}
