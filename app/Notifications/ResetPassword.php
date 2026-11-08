<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Laravel's password reset email, sent through the queue so the forgot-password form doesn't wait for the mail server.
 */
class ResetPassword extends BaseResetPassword implements ShouldQueue
{
    use Queueable;
}
