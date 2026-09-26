<?php

namespace Noo\StatamicLicenseHealthCheck\Tests;

use Mockery;
use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Health\Enums\Status;
use Statamic\Licensing\AddonLicense;
use Statamic\Licensing\LicenseManager;
use Statamic\Licensing\StatamicLicense;

class StatamicLicensesCheckTest extends TestCase
{
    #[Test]
    public function valid_licenses_pass(): void
    {
        $this->fakeLicenseManager(function ($manager): void {
            $manager->shouldReceive('response')->with('error')->andReturnNull();
            $manager->shouldReceive('valid')->andReturnTrue();
        });

        $result = (new StatamicLicensesCheck)->run();

        $this->assertSame(Status::ok()->value, $result->status->value);
        $this->assertSame('Valid', $result->shortSummary);
    }

    #[Test]
    public function invalid_statamic_and_addon_licenses_fail_with_details(): void
    {
        $this->fakeLicenseManager(function ($manager): void {
            $manager->shouldReceive('response')->with('error')->andReturnNull();
            $manager->shouldReceive('valid')->andReturnFalse();
            $manager->shouldReceive('statamicValid')->andReturnFalse();
            $manager->shouldReceive('statamic')->andReturn(
                Mockery::mock(StatamicLicense::class, ['invalidReason' => 'Outside license range'])
            );
            $manager->shouldReceive('addons')->andReturn(collect([
                Mockery::mock(AddonLicense::class, [
                    'valid' => false,
                    'name' => 'Bunny Stream',
                    'invalidReason' => 'Invalid edition',
                ]),
                Mockery::mock(AddonLicense::class, [
                    'valid' => true,
                    'name' => 'Super Link',
                    'invalidReason' => null,
                ]),
            ]));
        });

        $result = (new StatamicLicensesCheck)->run();

        $this->assertSame(Status::failed()->value, $result->status->value);
        $this->assertStringContainsString('Statamic: Outside license range', $result->notificationMessage);
        $this->assertStringContainsString('Bunny Stream: Invalid edition', $result->notificationMessage);
        $this->assertStringNotContainsString('Super Link', $result->notificationMessage);
        $this->assertSame(['Statamic: Outside license range', 'Bunny Stream: Invalid edition'], $result->meta['invalid']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function outpostErrorCodes(): array
    {
        return [
            'server error' => [500],
            'connection error cached as zero' => [0],
        ];
    }

    #[Test]
    #[DataProvider('outpostErrorCodes')]
    public function unavailable_outpost_skips_the_check(int $errorCode): void
    {
        $this->fakeLicenseManager(function ($manager) use ($errorCode): void {
            $manager->shouldReceive('response')->with('error')->andReturn($errorCode);
        });

        $result = (new StatamicLicensesCheck)->run();

        $this->assertSame(Status::skipped()->value, $result->status->value);
        $this->assertSame('Outpost unavailable', $result->shortSummary);
        $this->assertSame('', $result->notificationMessage);
    }

    private function fakeLicenseManager(callable $expectations): void
    {
        $manager = Mockery::mock(LicenseManager::class);
        $expectations($manager);
        $this->app->instance(LicenseManager::class, $manager);
    }
}
