<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Facades;

use Aenzenith\ElektraWeb\BookingApi\BookingApi as Module;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aenzenith\ElektraWeb\BookingApi\Resources\HotelScope hotel(int|string|null $hotelId = null)
 * @method static \Aenzenith\ElektraWeb\BookingApi\Resources\ConstantsResource constants()
 * @method static \Aenzenith\ElektraWeb\BookingApi\Auth\AccessToken login()
 * @method static \Aenzenith\ElektraWeb\BookingApi\Http\ApiResponse healthCheck()
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withCaptcha(string $token)
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withCredentials(\Aenzenith\ElektraWeb\BookingApi\Auth\Credentials $credentials)
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withLanguage(string $language)
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withCurrency(string $currency)
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withoutCache()
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApi withHeaders(array<string, string> $headers)
 * @method static \Aenzenith\ElektraWeb\BookingApi\BookingApiConfig config()
 * @method static void forgetToken()
 *
 * @see Module
 */
class BookingApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Module::class;
    }
}
