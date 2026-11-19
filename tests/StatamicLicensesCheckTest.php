<?php

namespace Noo\StatamicLicenseHealthCheck\Tests;

use Mockery;
use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Health\Enums\Status;
use Statamic\Licensing\AddonLicense;
use Statamic\Licensing\LicenseManager;
use Statamic\Licensing\StatamicLicense;

class StatamicLicensesCheckTest extends TestCase
{
    #[Test]
    public function invalid_statamic_and_addon_licenses_fail_with_details(): void
    {
        $this->mock(LicenseManager::class, function ($manager): void {
            $manager->shouldReceive('response')->with('error')->andReturnNull();
            $manager->shouldReceive('response')->with('statamic.valid')->andReturnFalse();
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
}
