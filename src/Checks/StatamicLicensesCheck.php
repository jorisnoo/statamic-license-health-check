<?php

namespace Noo\StatamicLicenseHealthCheck\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Statamic\Licensing\LicenseManager;

class StatamicLicensesCheck extends Check
{
    public function run(): Result
    {
        $licenses = app(LicenseManager::class);

        $result = Result::make();

        // Outpost failures are inconclusive, so do not alert as if the site's
        // licenses were invalid. Checking the response directly also catches
        // connection failures that Statamic caches with a zero error code.
        if ($licenses->response('error') !== null) {
            return new Result(
                status: Status::skipped(),
                shortSummary: 'Outpost unavailable',
            );
        }

        if ($licenses->valid()) {
            return $result
                ->shortSummary('Valid')
                ->ok('All Statamic licenses are valid.');
        }

        $invalid = $this->invalidLicenses($licenses);

        return $result
            ->shortSummary(count($invalid).' invalid')
            ->meta(['invalid' => $invalid])
            ->failed('Invalid Statamic licenses: '.implode('; ', $invalid));
    }

    /**
     * @return list<string>
     */
    private function invalidLicenses(LicenseManager $licenses): array
    {
        $invalid = [];

        if (! $licenses->statamicValid()) {
            $invalid[] = $this->describe('Statamic', $licenses->statamic()->invalidReason());
        }

        foreach ($licenses->addons()->reject->valid() as $addon) {
            $invalid[] = $this->describe($addon->name(), $addon->invalidReason());
        }

        return $invalid;
    }

    private function describe(string $name, ?string $reason): string
    {
        return $reason ? "{$name}: {$reason}" : $name;
    }
}
