<?php

namespace App\Http\Requests\Circle;

use App\Enums\ApprovalType;
use App\Models\Circle;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Inviting someone into a circle by the email address of their account.
 */
class StoreInviteRequest extends FormRequest
{
    /**
     * The circle's owners and admins can invite, once it's approved. A circle that isn't approved stays "not found"
     * to anyone outside it, rather than answering with a refusal.
     */
    public function authorize(): bool
    {
        Gate::authorize('view', $this->circle());

        return $this->user()->can('invite', $this->circle());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', Rule::exists(User::class, 'email')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.exists' => "There's no account with that email address. They'll need to join Commune Circles first.",
        ];
    }

    /**
     * Once the email belongs to a member, check where they stand with the circle.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $invitee = $this->invitee();
                $waiting = $this->circle()->approvals()->pending()->where('user_id', $invitee->id);

                $problem = match (true) {
                    $this->circle()->membershipOf($invitee) !== null => "They're already in this circle.",
                    (clone $waiting)->where('type', ApprovalType::Invite)->exists() => "They've already been invited, and the invite is still waiting.",
                    (clone $waiting)->where('type', ApprovalType::JoinRequest)->exists() => "They've already asked to join, so you can accept their request instead.",
                    default => null,
                };

                if ($problem !== null) {
                    $validator->errors()->add('email', $problem);
                }
            },
        ];
    }

    /**
     * The person being invited: the member with that email address.
     */
    public function invitee(): User
    {
        return User::query()->where('email', $this->string('email')->value())->sole();
    }

    /**
     * The circle they're being invited into, from the {circle} route parameter.
     */
    private function circle(): Circle
    {
        /** @var Circle $circle */
        $circle = $this->route('circle');

        return $circle;
    }
}
