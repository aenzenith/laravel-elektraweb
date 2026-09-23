<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Feature;

use Aenzenith\ElektraWeb\BookingApi\Auth\HotelUserCredentials;
use Aenzenith\ElektraWeb\BookingApi\BookingApi;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\AuthenticationException;
use Aenzenith\ElektraWeb\BookingApi\Exceptions\NotConfiguredException;
use Aenzenith\ElektraWeb\Facades\BookingApi as BookingApiFacade;
use Aenzenith\ElektraWeb\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class AuthenticationTest extends TestCase
{
    public function test_api_key_login_is_cached_and_reused_across_calls(): void
    {
        Http::fake($this->loginOk() + [
            self::BASE.'countries' => Http::response($this->fixture('countries.json')),
        ]);

        $api = $this->app->make(BookingApi::class)->withoutCache();

        $api->constants()->countries();
        $api->constants()->countries();

        $this->assertCount(1, $this->requestsTo('/login'));
        $login = $this->requestsTo('/login')[0];
        $this->assertSame('Bearer secret-api-key', $login->header('Authorization')[0]);
        $this->assertSame('{}', $login->body(), 'API-key login must send an empty JSON object body.');

        foreach ($this->requestsTo('/countries') as $request) {
            $this->assertSame('Bearer '.self::JWT, $request->header('Authorization')[0]);
        }
    }

    public function test_unauthorized_response_triggers_exactly_one_relogin_and_replays_the_request(): void
    {
        Http::fake([
            self::BASE.'login' => Http::sequence()
                ->push(['token' => 'stale.token.aaa'])
                ->push(['token' => 'fresh.token.bbb']),
            self::BASE.'countries' => Http::sequence()
                ->push(['message' => 'Unauthorized'], 401)
                ->push($this->fixture('countries.json')),
        ]);

        $countries = $this->app->make(BookingApi::class)->withoutCache()->constants()->countries();

        $this->assertSame('TR', $countries->first()->code2);
        $this->assertCount(2, $this->requestsTo('/login'));

        $sent = $this->requestsTo('/countries');
        $this->assertCount(2, $sent);
        $this->assertSame('Bearer stale.token.aaa', $sent[0]->header('Authorization')[0]);
        $this->assertSame('Bearer fresh.token.bbb', $sent[1]->header('Authorization')[0]);
    }

    public function test_rejected_login_throws_authentication_exception_with_provider_message(): void
    {
        Http::fake([
            self::BASE.'login' => Http::response(['message' => 'Invalid api key'], 401),
        ]);

        try {
            $this->app->make(BookingApi::class)->login();
            $this->fail('Expected AuthenticationException.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(401, $exception->status());
            $this->assertSame('Invalid api key', $exception->providerMessage());
        }
    }

    public function test_login_without_token_in_body_is_an_authentication_failure(): void
    {
        Http::fake([self::BASE.'login' => Http::response(['success' => true])]);

        $this->expectException(AuthenticationException::class);

        $this->app->make(BookingApi::class)->login();
    }

    public function test_missing_api_key_fails_lazily_with_not_configured(): void
    {
        config()->set('elektraweb.booking_api.auth.api_key', null);
        Http::fake();

        $api = $this->app->make(BookingApi::class);

        $this->expectException(NotConfiguredException::class);

        $api->constants()->countries();
    }

    public function test_hotel_user_credentials_are_sent_in_the_login_body(): void
    {
        Http::fake($this->loginOk() + [self::BASE.'countries' => Http::response([])]);

        $this->app->make(BookingApi::class)
            ->withoutCache()
            ->withCredentials(new HotelUserCredentials('26780', 'frontdesk', 'pa55'))
            ->constants()
            ->countries();

        $login = $this->requestsTo('/login')[0];
        $this->assertSame(
            ['hotel-id' => '26780', 'usercode' => 'frontdesk', 'password' => 'pa55'],
            json_decode($login->body(), true)
        );
        $this->assertFalse($login->hasHeader('Authorization'));
    }

    public function test_captcha_driver_sends_x_captcha_header_and_never_logs_in(): void
    {
        config()->set('elektraweb.booking_api.auth.driver', 'captcha');
        Http::fake([self::BASE.'countries' => Http::response([])]);

        BookingApiFacade::withCaptcha('recaptcha-token')->withoutCache()->constants()->countries();

        $this->assertCount(0, $this->requestsTo('/login'));
        Http::assertSent(fn (Request $request): bool => $request->header('x-captcha') === ['recaptcha-token']
            && ! $request->hasHeader('Authorization'));
    }

    public function test_captcha_driver_without_token_is_not_configured(): void
    {
        config()->set('elektraweb.booking_api.auth.driver', 'captcha');
        Http::fake();

        $this->expectException(NotConfiguredException::class);

        BookingApiFacade::withoutCache()->constants()->countries();
    }

    public function test_forget_token_forces_a_new_login(): void
    {
        Http::fake($this->loginOk() + [self::BASE.'countries' => Http::response([])]);

        $api = $this->app->make(BookingApi::class)->withoutCache();
        $api->constants()->countries();
        $api->forgetToken();
        $api->constants()->countries();

        $this->assertCount(2, $this->requestsTo('/login'));
    }
}
