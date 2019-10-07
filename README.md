
## Memory scopes

See which data is available where. What code runs in which process

```bash
run memory-scopes.php
curl http://localhost:8080/
docker stop swoole
```

### SWOOLE_PROCESS

Everything before SwooleHttpServer->start() is available in every process
Everything after SwooleHttpServer->start() is available within given process

onStart is on master process, can use global
onShutdown is on master process, case use global and onStart
onManagerStart is on manager process, can use global
onWorkerStart is worker n process, can use global
onRequest is worker n process, can use global and onWorkerStart

```bash
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

```bash
> docker exec -it swoole ps aux
USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
root         1  0.1  2.1 510604 44104 pts/0    Ssl+ 19:42   0:00 swoole-master
root         6  0.0  0.6 362248 12376 pts/0    S+   19:42   0:00 swoole-manager
root         9  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-0
root        10  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-1
root        11  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-2
root        12  0.0  0.8 358636 16372 pts/0    S+   19:42   0:00 swoole-worker-3
```

Note that the master has an `l` in the STAT, which means it is multi threaded


### SWOOLE_BASE

Differences;
 - There is not manager process
 - onStart scope is now also globally available

```bash
> docker logs swoole
 1 [swoole-master   ] run
 1 [swoole-master   ] run, onStart
 6 [swoole-worker-0 ] run, onStart, onWorkerStart
 7 [swoole-worker-1 ] run, onStart, onWorkerStart
 8 [swoole-worker-2 ] run, onStart, onWorkerStart
 9 [swoole-worker-3 ] run, onStart, onWorkerStart
 7 [swoole-worker-1 ] run, onStart, onWorkerStart, onRequest
 7 [swoole-worker-1 ] run, onStart, onWorkerStart, onRequest (REUSED)
 8 [swoole-worker-2 ] run, onStart, onWorkerStart, onRequest
 8 [swoole-worker-2 ] run, onStart, onWorkerStart, onRequest (REUSED)
 1 [swoole-master   ] run, onStart, onShutdown
```

```bash
> docker exec -it swoole ps aux
USER       PID %CPU %MEM    VSZ   RSS TTY      STAT START   TIME COMMAND
root         1  0.2  2.0 362180 41168 pts/0    Ss+  20:00   0:00 swoole-master
root         6  0.0  0.7 362792 14280 pts/0    S+   20:00   0:00 swoole-worker-0
root         7  0.0  0.7 362792 14280 pts/0    S+   20:00   0:00 swoole-worker-1
root         8  0.0  0.7 362792 14280 pts/0    S+   20:00   0:00 swoole-worker-2
root         9  0.0  0.7 362792 14280 pts/0    S+   20:00   0:00 swoole-worker-3
```

Note that the master is not multi threaded.

## Async and Pooling

```bash
run async-pooling.php
ab -n 20 -c 4 http://localhost:8080/
docker stop swoole
```

Having a server with 2 workers, each having a database pool of 2  
Means being able to process 4 requests concurrently!

Each database call takes exactly 1 second (sleep).

```
n = number of request
c = concurrent requests
s = seconds to complete
```

This means `ab -n 20 -c 4` can complete in about 5 seconds. (20n / 4c = 5s)  
This means `ab -n 20 -c 10` still takes 5 seconds, database pool being the bottleneck.  
Requests will take longer though, as it will accept all 10 at the same time

Without `Runtime::enableCoroutine()` it will take about 10 seconds, as it will block on the 1s sleep.  
20 request block 20 seconds split over 2 workers = 10 seconds total time