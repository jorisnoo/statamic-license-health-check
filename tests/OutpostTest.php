<?php

namespace Noo\StatamicLicenseHealthCheck\Tests;

use Composer\InstalledVersions;
use Facades\Statamic\Version;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Noo\StatamicLicenseHealthCheck\Checks\StatamicLicensesCheck;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Statamic\Licensing\LicenseManager;
use Statamic\Licensing\Outpost;
use TypeError;

class OutpostTest extends TestCase
{
    private function runCheck(array $replies): Result
    {
        Version::shouldReceive('get')->andReturn('6.0.0');
        $this->app['translator']->addNamespace('statamic', InstalledVersions::getInstallPath('statamic/cms').'/lang');
        config(['cache.default' => 'array']);

        $client = new Client(['handler' => MockHandler::createWithMiddleware($replies)]);
        $outpost = new class($client) extends Outpost
        {
            public function payload(): array
            {
                return [];
            }
        };
        $this->app->instance(LicenseManager::class, new LicenseManager($outpost));

        return (new StatamicLicensesCheck)->run();
    }

    public static function requestErrors(): array
    {
        return [
            'rejected license data' => [422, '{"errors":{"host":["Unrecognized site."]}}', 'warning'],
            'forbidden' => [403, '', 'warning'],
            'rate limit' => [429, '', 'skipped'],
            'service unavailable' => [503, '', 'skipped'],
            'connection failure' => [0, '', 'skipped'],
        ];
    }

    #[Test]
    #[DataProvider('requestErrors')]
    public function request_errors_distinguish_outages_from_actionable_problems(int $code, string $body, string $status): void
    {
        $reply = $code === 0
            ? new ConnectException('Offline', new Request('POST', Outpost::ENDPOINT))
            : new Response($code, ['Retry-After' => '60'], $body);
        $result = $this->runCheck([$reply]);

        $this->assertSame($status, $result->status->value);
        $this->assertSame($code, $result->meta['error_code']);
        $this->assertStringContainsString("Outpost ({$code}): ", $result->notificationMessage);
        if ($code === 422) {
            $this->assertStringContainsString('Unrecognized site.', $result->notificationMessage);
        }
    }

    #[Test]
    public function a_key_file_that_cannot_be_decrypted_warns_even_for_code_500(): void
    {
        config(['statamic.system.license_key' => str_repeat('a', 16)]);
        File::partialMock()->shouldReceive('exists')->with(storage_path('license.key'))->andReturnTrue();
        File::shouldReceive('get')->with(storage_path('license.key'))->andReturn('unreadable');

        $result = $this->runCheck([]);

        $this->assertSame(Status::warning()->value, $result->status->value);
        $this->assertSame(500, $result->meta['error_code']);
        $this->assertStringContainsString(__('statamic::messages.outpost_license_key_error'), $result->notificationMessage);
    }

    #[Test]
    public function non_json_does_not_abort_the_run_or_declare_the_license_invalid(): void
    {
        $result = $this->runCheck([new Response(200, [], '<html>Maintenance</html>')]);

        // Older Outpost versions throw; newer ones classify this as a 500.
        $this->assertContains($result->status->value, [Status::warning()->value, Status::skipped()->value]);
        $this->assertNotEmpty($result->notificationMessage);
    }

    #[Test]
    public function an_unprocessable_validation_response_warns_and_reports_the_error_privately(): void
    {
        Exceptions::fake();
        $result = $this->runCheck([new Response(422, [], '{"errors":"unexpected"}')]);

        $this->assertSame(Status::warning()->value, $result->status->value);
        $this->assertStringContainsString('application log', $result->notificationMessage);
        Exceptions::assertReported(TypeError::class);
    }

    public static function verdicts(): array
    {
        return [
            'valid' => ['{"statamic":{"valid":true}}', 'ok'],
            'invalid' => ['{"statamic":{"valid":false}}', 'failed'],
            'no statamic record' => ['{"message":"Maintenance"}', 'warning'],
            'no verdict' => ['{"statamic":{}}', 'warning'],
        ];
    }

    #[Test]
    #[DataProvider('verdicts')]
    public function a_license_verdict_is_required(string $body, string $status): void
    {
        $result = $this->runCheck([new Response(200, [], $body)]);

        $this->assertSame($status, $result->status->value);
    }
}
