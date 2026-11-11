<?php

namespace App\Http\Controllers;

use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use App\Models\Circle;
use App\Notifications\Circle\JoinRequestReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class JoinRequestController extends Controller
{
    /**
     * Ask to join the circle (asking again after a refusal reopens the request), email its owners and admins,
     * then go back.
     */
    public function store(Request $request, Circle $circle): RedirectResponse
    {
        // NOTE: view first, so a circle that isn't approved stays "not found" instead of answering with a refusal.
        Gate::authorize('view', $circle);
        Gate::authorize('requestToJoin', $circle);

        $asker = $request->user();

        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        Notification::send($circle->managers, new JoinRequestReceived($circle, $asker));

        Inertia::flash('status', 'Your request to join has been sent.');

        return back();
    }
}
