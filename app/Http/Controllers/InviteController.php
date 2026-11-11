<?php

namespace App\Http\Controllers;

use App\Enums\Approval\ApprovalType;
use App\Http\Requests\Circle\StoreInviteRequest;
use App\Models\Approval;
use App\Models\Circle;
use App\Notifications\Circle\InviteReceived;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InviteController extends Controller
{
    /**
     * Invite a member into the circle (an earlier declined invite is reopened), email them, then go back.
     */
    public function store(StoreInviteRequest $request, Circle $circle): RedirectResponse
    {
        $invitee = $request->invitee();
        $inviter = $request->user();

        Approval::open(ApprovalType::Invite, $circle, $invitee, $inviter);
        $invitee->notify(new InviteReceived($circle, $inviter));

        Inertia::flash('status', "Invite sent to {$invitee->first_name} {$invitee->last_name}.");

        return back();
    }
}
