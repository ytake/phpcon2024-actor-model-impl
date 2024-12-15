<?php

declare(strict_types=1);

use Calculator\Command\Add;
use Calculator\Command\PrintResult;
use Calculator\InMemoryStateProvider;
use Calculator\PersistenceActor;
use Phluxor\ActorSystem;
use Phluxor\Persistence\EventSourcedBehavior;
use Phluxor\Persistence\InMemoryProvider;
use Swoole\Coroutine;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

require_once __DIR__ . '/vendor/autoload.php';

run(function () {
    go(function () {
        $system = ActorSystem::create();
        $props = ActorSystem\Props::fromProducer(
            fn() => new PersistenceActor(),
            ActorSystem\Props::withReceiverMiddleware(
                new EventSourcedBehavior(
                    new InMemoryStateProvider(new InMemoryProvider(1))
                )
            )
        );
        $spawn = $system->root()->spawnNamed($props, 'calculator');
        $system->root()->send($spawn->getRef(), new Add(10));
        $system->root()->send($spawn->getRef(), new Add(10));
        $system->root()->send($spawn->getRef(), new Add(10));
        $system->root()->send($spawn->getRef(), new PrintResult());
        $system->root()->poison($spawn->getRef());
        \Swoole\Coroutine::sleep(0.1);
        $spawn = $system->root()->spawnNamed($props, 'calculator');
        $system->root()->send($spawn->getRef(), new PrintResult());
    });
});
