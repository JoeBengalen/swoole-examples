<?php
declare(strict_types=1);
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;

final class App
{
    /**
     * @var string
     */
    private const NAME = 'swoole';

    public function run(): void
    {
        swoole_set_process_name(sprintf('%s-master', self::NAME));
        MemTest::log(__FUNCTION__);

        $server = new SwooleHttpServer('0.0.0.0', 8080, SWOOLE_PROCESS);
        $server->set([
            'worker_num' => 4,
        ]);

        $server->on('start', [$this, 'onStart']);
        $server->on('managerstart', [$this, 'onManagerStart']);
        $server->on('workerstart', [$this, 'onWorkerStart']);
        $server->on('request', [$this, 'onRequest']);
        $server->on('shutdown', [$this, 'onShutdown']);

        $server->start();
    }

    public function onStart(SwooleHttpServer $server): void
    {
        MemTest::log(__FUNCTION__);
    }

    public function onManagerStart(SwooleHttpServer $server): void
    {
        swoole_set_process_name(sprintf('%s-manager', self::NAME));
        MemTest::log(__FUNCTION__);
    }

    public function onWorkerStart(SwooleHttpServer $server, int $workerId): void
    {
        swoole_set_process_name(sprintf('%s-worker-%d', self::NAME, $workerId));
        MemTest::log(__FUNCTION__);
    }

    public function onRequest(SwooleHttpRequest $request, SwooleHttpResponse $response): void
    {
        MemTest::log(__FUNCTION__);
        $response->end('hello');
    }

    public function onShutdown(SwooleHttpServer $server): void
    {
        MemTest::log(__FUNCTION__);
    }
}

final class MemTest
{
    public static function log(string $name)
    {
        $pid = posix_getpid();
        $process = trim(file_get_contents(sprintf('/proc/%d/cmdline', $pid)));

        static $scoped = [];

        $first = false;
        if (!in_array($name, $scoped)) {
            $scoped[] = $name;
            $first = true;
        }

        printf("% 2d [% -16s] %s\n", $pid, $process, implode(', ', $scoped) . ($first ? '' : ' (REUSED)'));
    }
}

$app = new App();
$app->run();
