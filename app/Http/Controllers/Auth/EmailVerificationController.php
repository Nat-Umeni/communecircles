<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationController extends Controller
{
    /**
     * Ask a member who hasn't confirmed their email address yet to check their inbox.
     */
    public function notice(Request $request): Response|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('home');
        }

        return Inertia::render('auth/VerifyEmail');
    }

    /**
     * Confirm the member's email address from the signed link in their confirmation email.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('home');
        }

        $request->fulfill();

        Inertia::flash('status', __('Thanks, your email address is confirmed.'));

        return to_route('home');
    }

    /**
     * Email the member a new confirmation link.
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        Inertia::flash('status', __("We've sent a new confirmation link to your email address."));

        return back();
    }
}
