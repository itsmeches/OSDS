<?php

namespace Tests\Unit;

use Illuminate\Foundation\Console\ServeCommand;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WindowsServeUploadEnvironmentTest extends TestCase
{
    public function test_windows_artisan_server_keeps_a_writable_upload_temporary_directory(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('Windows-only development server environment.');
        }
        foreach (['TEMP', 'TMP', 'TMPDIR'] as $variable) {
            $this->assertContains($variable, ServeCommand::$passthroughVariables);
        }
        $environment = [];
        foreach (getenv() as $key => $value) {
            $environment[$key] = in_array(strtoupper($key), array_map('strtoupper', ServeCommand::$passthroughVariables), true) ? $value : false;
        }
        $process = new Process([PHP_BINARY, '-r', 'echo json_encode(["directory" => sys_get_temp_dir(), "writable" => is_writable(sys_get_temp_dir())]);'], base_path(), $environment);
        $process->mustRun();
        $data = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($data['writable']);
        $this->assertSame(strtolower(sys_get_temp_dir()), strtolower($data['directory']));
    }
}
