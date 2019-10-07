<?php
declare(strict_types=1);

use Swoole\Coroutine\Channel;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Runtime;

const NAME = 'swoole';

Runtime::enableCoroutine();

$registry = new ArrayObject();

swoole_set_process_name(sprintf('%s-master', NAME));
$server = new SwooleHttpServer('0.0.0.0', 8080, SWOOLE_PROCESS);
$server->set([
    'worker_num' => 2,
    'dispatch_mode' => 1,
]);

$server->on('managerStart', function (SwooleHttpServer $server) {
    swoole_set_process_name(sprintf('%s-manager', NAME));
    $logger = new Logger(sprintf('%s-manager', NAME));
    $logger->log('onManagerStart');
});

$server->on('workerStart', function (SwooleHttpServer $server, int $workerId) use ($registry) {
    swoole_set_process_name(sprintf('%s-worker-%d', NAME, $workerId));
    $logger = new Logger(sprintf('%s-worker-%d', NAME, $workerId));
    $logger->log("onWorkerStart");
    $registry->logger = $logger;
    $registry->db = new DatabasePool(2, $logger);
});

$server->on('request', function (SwooleHttpRequest $request, SwooleHttpResponse $response) use ($registry) {
    /** @var DatabasePool $pool */
    $pool = $registry->db;
    $database = $pool->borrow();
    $database->execute();
    $pool->return($database);

    $durationMs = (microtime(true) - $request->server['request_time_float']) * 1000;
    $registry->logger->log(sprintf('Request %s took %dms', $request->fd, $durationMs));
    $response->end('hello');
});

$server->start();
class DatabasePool
{
    /**
     * @var Channel
     */
    private $pool;

    public function __construct(int $size, Logger $logger)
    {
        $this->pool = new Channel($size);
        while (!$this->pool->isFull()) {
            $this->pool->push(new Database($logger, $this->pool->length()));
        }
    }

    public function borrow(): Database
    {
        return $this->pool->pop();
    }

    public function return(Database $database): void
    {
        $this->pool->push($database);
    }
}

class Database
{
    private $logger;
    private $connection;

    public function __construct(Logger $logger, int $connection)
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->logger->log('Connected to database ' . $connection);
    }

    public function execute(): void
    {
        sleep(1);
        $this->logger->log('Executed database query on ' . $this->connection);
    }
}

class Logger
{
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function log(string $message, array $context = []): void
    {
        printf("%s [% -16s] %s\n", (new DateTime())->format('H:i:s.v'), $this->name, $message);
    }
}
