<?php

declare(strict_types=1);

namespace Tests\Unit\LogParsers;

use PHPUnit\Framework\Attributes\CoversClass;
use App\Services\LogParsers\DockerLogParser;
use App\Services\LogParsers\GenericLogParser;
use App\Services\LogParsers\LaravelLogParser;
use App\Services\LogParsers\LogParserFactory;
use App\Services\LogParsers\MysqlLogParser;
use App\Services\LogParsers\NginxLogParser;
use App\Services\LogParsers\PhpLogParser;
use App\Services\LogParsers\SystemLogParser;
use PHPUnit\Framework\TestCase;

#[CoversClass(\App\Services\LogParsers\LogParserFactory::class)]
#[CoversClass(\App\Services\LogParsers\NginxLogParser::class)]
#[CoversClass(\App\Services\LogParsers\LaravelLogParser::class)]
#[CoversClass(\App\Services\LogParsers\PhpLogParser::class)]
#[CoversClass(\App\Services\LogParsers\MysqlLogParser::class)]
#[CoversClass(\App\Services\LogParsers\SystemLogParser::class)]
#[CoversClass(\App\Services\LogParsers\DockerLogParser::class)]
#[CoversClass(\App\Services\LogParsers\GenericLogParser::class)]
#[CoversClass(\App\Services\LogParsers\AbstractLogParser::class)]
final class LogParserFactoryTest extends TestCase
{
    private LogParserFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new LogParserFactory();
    }

    public function testGetParserReturnsCorrectTypes(): void
    {
        $this->assertInstanceOf(NginxLogParser::class, $this->factory->getParser('nginx'));
        $this->assertInstanceOf(LaravelLogParser::class, $this->factory->getParser('laravel'));
        $this->assertInstanceOf(PhpLogParser::class, $this->factory->getParser('php'));
        $this->assertInstanceOf(MysqlLogParser::class, $this->factory->getParser('mysql'));
        $this->assertInstanceOf(SystemLogParser::class, $this->factory->getParser('system'));
        $this->assertInstanceOf(DockerLogParser::class, $this->factory->getParser('docker'));
        $this->assertInstanceOf(GenericLogParser::class, $this->factory->getParser('unknown'));
    }

    public function testGetParserIsCaseInsensitive(): void
    {
        $this->assertInstanceOf(NginxLogParser::class, $this->factory->getParser('NGINX'));
        $this->assertInstanceOf(LaravelLogParser::class, $this->factory->getParser('Laravel'));
    }

    public function testGetAvailableTypes(): void
    {
        $types = $this->factory->getAvailableTypes();

        $this->assertContains('nginx', $types);
        $this->assertContains('laravel', $types);
        $this->assertContains('php', $types);
        $this->assertContains('mysql', $types);
        $this->assertContains('system', $types);
        $this->assertContains('docker', $types);
    }

    public function testParseNginxErrorLog(): void
    {
        $content = "2025/11/28 10:30:45 [error] 123#123: *456 upstream timed out";

        $logs = $this->factory->parse('nginx', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('nginx', $logs[0]['source']);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertStringContains('upstream timed out', $logs[0]['message']);
    }

    public function testParseNginxAccessLog(): void
    {
        $content = '192.168.1.1 - - [28/Nov/2025:10:30:45 +0000] "GET / HTTP/1.1" 200 1234';

        $logs = $this->factory->parse('nginx', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('nginx', $logs[0]['source']);
        $this->assertEquals('info', $logs[0]['level']);
    }

    public function testParseLaravelLog(): void
    {
        $content = '[2025-11-28 10:30:45] local.ERROR: Test error message';

        $logs = $this->factory->parse('laravel', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('laravel', $logs[0]['source']);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('Test error message', $logs[0]['message']);
    }

    public function testParseLaravelLogWithStackTrace(): void
    {
        $content = <<<'LOG'
[2025-11-28 10:30:45] local.ERROR: Test error
#0 /app/test.php(123): doSomething()
#1 /app/main.php(456): run()
LOG;

        $logs = $this->factory->parse('laravel', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('laravel', $logs[0]['source']);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertStringContains('#0', $logs[0]['message']);
        $this->assertEquals('/app/test.php', $logs[0]['file_path']);
        $this->assertEquals(123, $logs[0]['line_number']);
    }

    public function testParsePhpLog(): void
    {
        $content = '[28-Nov-2025 10:30:45 UTC] PHP Warning: Undefined variable in /app/test.php on line 123';

        $logs = $this->factory->parse('php', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('php', $logs[0]['source']);
        $this->assertEquals('warning', $logs[0]['level']);
        $this->assertEquals('/app/test.php', $logs[0]['file_path']);
        $this->assertEquals(123, $logs[0]['line_number']);
    }

    public function testParseMysqlLog(): void
    {
        $content = '2025-11-28T10:30:45.123456Z 1 [ERROR] Query failed';

        $logs = $this->factory->parse('mysql', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('mysql', $logs[0]['source']);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('Query failed', $logs[0]['message']);
    }

    public function testParseSystemLog(): void
    {
        $content = 'Nov 28 10:30:45 myserver nginx[123]: Connection accepted';

        $logs = $this->factory->parse('system', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('system', $logs[0]['source']);
        $this->assertEquals('info', $logs[0]['level']);
        $this->assertEquals('Connection accepted', $logs[0]['message']);
        $this->assertEquals('myserver', $logs[0]['context']['hostname']);
        $this->assertEquals('nginx[123]', $logs[0]['context']['service']);
    }

    public function testParseDockerLogWithTimestamp(): void
    {
        $content = '2025-11-28T10:30:45.123456Z Container started successfully';

        $logs = $this->factory->parse('docker', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('docker', $logs[0]['source']);
        $this->assertEquals('info', $logs[0]['level']);
        $this->assertEquals('Container started successfully', $logs[0]['message']);
    }

    public function testParseDockerLogWithoutTimestamp(): void
    {
        $content = 'Ready to accept connections';

        $logs = $this->factory->parse('docker', $content);

        $this->assertCount(1, $logs);
        $this->assertEquals('docker', $logs[0]['source']);
        $this->assertEquals('Ready to accept connections', $logs[0]['message']);
    }

    public function testParseGenericLog(): void
    {
        $content = "Line 1\nLine 2\nLine 3";

        $logs = $this->factory->parse('custom', $content);

        $this->assertCount(3, $logs);
        $this->assertEquals('custom', $logs[0]['source']);
        $this->assertEquals('Line 1', $logs[0]['message']);
        $this->assertEquals('Line 2', $logs[1]['message']);
        $this->assertEquals('Line 3', $logs[2]['message']);
    }

    public function testEmptyContentReturnsEmptyArray(): void
    {
        $this->assertEmpty($this->factory->parse('nginx', ''));
        $this->assertEmpty($this->factory->parse('laravel', ''));
        $this->assertEmpty($this->factory->parse('generic', "\n\n\n"));
    }

    public function testLevelNormalization(): void
    {
        // Test various level formats
        $errorLog = "2025/11/28 10:30:45 [err] 1#1: *1 error message";
        $logs = $this->factory->parse('nginx', $errorLog);
        $this->assertEquals('error', $logs[0]['level']);

        $warnLog = "2025/11/28 10:30:45 [warn] 1#1: *1 warning message";
        $logs = $this->factory->parse('nginx', $warnLog);
        $this->assertEquals('warning', $logs[0]['level']);

        $critLog = "2025/11/28 10:30:45 [crit] 1#1: *1 critical message";
        $logs = $this->factory->parse('nginx', $critLog);
        $this->assertEquals('critical', $logs[0]['level']);
    }

    /**
     * Helper to check if string contains substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
