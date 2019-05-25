
## Memory scopes

See which data is available where. What code runs in which process

```bash
run memory-scopes.php
curl http://localhost:8080/
docker stop swoole
```

```
> docker logs swoole
 1 [swoole-master   ] run
 1 [swoole-master   ] run, onStart
 6 [swoole-manager  ] run, onManagerStart
 9 [swoole-worker-0 ] run, onWorkerStart
12 [swoole-worker-3 ] run, onWorkerStart
11 [swoole-worker-2 ] run, onWorkerStart
10 [swoole-worker-1 ] run, onWorkerStart
10 [swoole-worker-1 ] run, onWorkerStart, onRequest
10 [swoole-worker-1 ] run, onWorkerStart, onRequest (REUSED)
12 [swoole-worker-3 ] run, onWorkerStart, onRequest
12 [swoole-worker-3 ] run, onWorkerStart, onRequest (REUSED)
 1 [swoole-master   ] run, onStart, onShutdown
```

```
> docker exec -it swoole ps aux
USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
root         1  0.1  2.1 510604 44104 pts/0    Ssl+ 19:42   0:00 swoole-master
root         6  0.0  0.6 362248 12376 pts/0    S+   19:42   0:00 swoole-manager
root         9  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-0
root        10  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-1
root        11  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-2
root        12  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-3
```

Everything before SwooleHttpServer->start() is available in every process
Everything after SwooleHttpServer->start() is available within given process

onStart is on master process, can use global
onShutdown is on master process, case use global and onStart
onManagerStart is on manager process, can use global
onWorkerStart is worker n process, can use global
onRequest is worker n process, can use global and onWorkerStart

