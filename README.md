# tiny-sysbench-php


## step 0. install docker
eg. https://docs.docker.com/desktop/install/linux-install/

```
$ sudo usermod -aG docker $USER
$ id $USER
$ sudo systemctl enable docker
$ sudo systemctl start docker
$ sudo systemctl status docker

$ exit

# relogin
$ docker run hello-world
```

## step 1. download tiny-sysbench-php
```
git clone https://github.com/dulao5/tiny-sysbench-php.git

cd tiny-sysbench-php

vi docker-compose.yml # edit php environment vars
```

## step 2. docker-compose up
```
docker compose up -d --build

# confirm
docker compose exec -it gatling curl http://nginx/

# confirm gatling threads
grep constantConcurrentUsers gatling/scenario/base.scala
```

## step 3. run gatling
```

docker compose exec -it gatling gatling

```
