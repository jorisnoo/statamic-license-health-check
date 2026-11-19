<?php

namespace Noo\StatamicLicenseHealthCheck\Checks;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Statamic\Licensing\LicenseManager;
use Throwable;

class StatamicLicensesCheck extends Check
{
    public function run(): Result
    {
        try {
            return $this->checkLicenses(app(LicenseManager::class));
        } catch (Throwable $e) {
            report($e);

            return Result::make()->shortSummary('License status unknown')
                ->warning('Could not read the Statamic license status. See the application log for details.');
        }
    }

    private function checkLicenses(LicenseManager $licenses): Result
    {
        $code = $licenses->response('error');

        if ($code !== null) {
            $transient = ! $licenses->usingLicenseKeyFile() && ($code === 0 || $code === 429 || ($code >= 500 && $code < 600));

            return (new Result(
                status: $transient ? Status::skipped() : Status::warning(),
                notificationMessage: "Outpost ({$code}): ".$licenses->requestFailureMessage(),
                shortSummary: $transient ? 'Outpost unavailable' : 'License check needs attention',
            ))->meta(['error_code' => $code]);
        }

        $result = Result::make();

        if (! is_bool($licenses->response('statamic.valid'))) {
            return $result->shortSummary('License status unknown')
                ->warning('Outpost did not return a Statamic license validity verdict.');
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
