<?php

declare(strict_types=1);

namespace Aenzenith\ElektraWeb\Tests\Unit;

use Aenzenith\ElektraWeb\BookingApi\Auth\AccessToken;
use Aenzenith\ElektraWeb\BookingApi\Data\HotelDefinitions;
use Aenzenith\ElektraWeb\BookingApi\Data\Offer;
use Aenzenith\ElektraWeb\BookingApi\Data\OfferCollection;
use Aenzenith\ElektraWeb\BookingApi\Requests\Phone;
use Aenzenith\ElektraWeb\Tests\TestCase;

final class DataMappingTest extends TestCase
{
    public function test_hotel_definitions_fixture_maps_every_section(): void
    {
        $definitions = HotelDefinitions::fromArray($this->fixture('hotel-definitions.json'));

        $this->assertCount(5, $definitions->roomTypes);
        $this->assertCount(2, $definitions->boardTypes);
        $this->assertCount(2, $definitions->rateTypes);

        $suite = $definitions->roomType(401040);
        $this->assertNotNull($suite);
        $this->assertSame('SU', $suite->code);
        $this->assertSame(5, $suite->rules->maxAdults);
        $this->assertSame(75.0, $suite->area);
        $this->assertTrue($suite->hasBalcony);

        $html = $definitions->roomType(415228);
        $this->assertStringNotContainsString('<li>', (string) $html?->plainDescription());
        $this->assertStringContainsString('Çift Kişilik Yatak', (string) $html?->plainDescription());

        $this->assertTrue($definitions->rateType(26769)?->isRefundable());
        $this->assertFalse($definitions->rateType(39735)?->isRefundable());
    }

    public function test_offer_equivalence_ignores_id_but_matches_rate_identity(): void
    {
        $offers = OfferCollection::fromRows($this->fixture('price.json'));
        $expected = $offers->first();

        $renamed = Offer::fromArray(array_replace($expected->raw, ['id' => 'other-id', 'discounted-price' => 4600]));

        $this->assertNull($offers->find('other-id'));
        $this->assertSame($expected->id, $offers->equivalentTo($renamed)?->id);
    }

    public function test_offer_is_not_bookable_when_stop_sell_or_sold_out(): void
    {
        $base = OfferCollection::fromRows($this->fixture('price.json'))->first()->raw;

        $this->assertFalse(Offer::fromArray(array_replace($base, ['stop-sell' => true]))->isBookable());
        $this->assertFalse(Offer::fromArray(array_replace($base, ['room-tosell' => 0]))->isBookable());
        $this->assertFalse(Offer::fromArray(array_replace($base, ['stop-sell-closed-to-arrival' => true]))->isBookable());
        $this->assertTrue(Offer::fromArray($base)->isBookable());
    }

    public function test_access_token_reads_expiry_from_jwt_claims(): void
    {
        $exp = time() + 3600;
        $payload = rtrim(strtr(base64_encode(json_encode(['exp' => $exp])), '+/', '-_'), '=');
        $token = AccessToken::fromString('eyJhbGciOiJIUzI1NiJ9.'.$payload.'.sig');

        $this->assertSame($exp, $token->expiresAt?->getTimestamp());
        $this->assertFalse($token->isExpired());

        $expired = AccessToken::fromString('eyJhbGciOiJIUzI1NiJ9.'.rtrim(strtr(base64_encode(json_encode(['exp' => time() - 10])), '+/', '-_'), '=').'.sig');
        $this->assertTrue($expired->isExpired());
    }

    public function test_phone_normalisation_keeps_international_prefix_only(): void
    {
        $this->assertSame('+905551112233', Phone::normalize('+90 (555) 111 22 33'));
        $this->assertSame('+905551112233', Phone::normalize('0090 555 111 22 33'));
        $this->assertSame('05551112233', Phone::normalize('0555 111 22 33'));
        $this->assertNull(Phone::normalize('   '));
        $this->assertNull(Phone::normalize(null));
    }
}
