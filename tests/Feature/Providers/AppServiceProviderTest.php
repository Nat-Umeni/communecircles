<?php

namespace Tests\Feature\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_resources_are_sent_without_a_data_wrapper_so_page_props_keep_their_shape(): void
    {
        $response = JsonResource::make(['name' => 'Seed Exchange'])->toResponse(request());

        $this->assertSame(['name' => 'Seed Exchange'], $response->getData(true));
    }

    public function test_models_are_strict_outside_production(): void
    {
        $this->assertTrue(Model::preventsLazyLoading(), 'lazy loading (an N+1) throws');
        $this->assertTrue(Model::preventsSilentlyDiscardingAttributes(), 'filling an attribute that is not fillable throws');
        $this->assertTrue(Model::preventsAccessingMissingAttributes(), 'reading an attribute that was not selected throws');
    }

    public function test_models_forgive_in_production_so_a_slip_there_is_not_an_error_page(): void
    {
        $this->app['env'] = 'production';

        try {
            (new AppServiceProvider($this->app))->boot();

            $this->assertFalse(Model::preventsLazyLoading());
            $this->assertFalse(Model::preventsSilentlyDiscardingAttributes());
            $this->assertFalse(Model::preventsAccessingMissingAttributes());
        } finally {
            // NOTE: these switches are static, so the next test in this process would otherwise run lenient.
            Model::shouldBeStrict();
        }
    }

    public function test_default_password_rules_accept_a_strong_password(): void
    {
        $validator = Validator::make(['password' => 'Analytical1!'], ['password' => Password::defaults()]);

        $this->assertTrue($validator->passes());
    }

    #[DataProvider('weakPasswords')]
    public function test_default_password_rules_reject_a_weak_password_with_its_message(string $password, string $message): void
    {
        $validator = Validator::make(['password' => $password], ['password' => Password::defaults()]);

        $this->assertContains($message, $validator->errors()->get('password'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'shorter than 8 characters' => ['Ab1!', 'The password field must be at least 8 characters.'],
            'longer than 256 characters' => [str_repeat('a', 255).'1!', 'The password field must not be greater than 256 characters.'],
            'without a letter' => ['12345678!', 'The password field must contain at least one letter.'],
            'without a number' => ['Analytical!', 'The password field must contain at least one number.'],
            'without a symbol' => ['Analytical1', 'The password field must contain at least one symbol.'],
        ];
    }

    public function test_using_up_the_login_limit_does_not_block_registering(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), []);
        }

        $response = $this->post(route('auth.register.store'), []);

        $response->assertRedirect();
    }

    public function test_using_up_the_limit_from_one_address_does_not_block_another(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), []);
        }

        $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])->post(route('login.store'), []);

        $response->assertRedirect();
    }
}
