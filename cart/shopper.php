<?php

declare(strict_types=1);

use Cart\Basket\Command\AddItem;
use Cart\InMemoryStateProvider;
use Cart\Item;
use Cart\Shopper\Actor;
use Cart\Shopper\Command\PayBasket;
use Phluxor\ActorSystem;
use Phluxor\Persistence\InMemoryProvider;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

require_once __DIR__ . '/vendor/autoload.php';

function stateProvider(): InMemoryStateProvider
{
    return new InMemoryStateProvider(new InMemoryProvider(1));
}

$shopperId = '1';
$provider = stateProvider();

run(function () {
    go(function () {
        $shopperId = 1;
        $system = ActorSystem::create();
        $system->getEventStream()->subscribe(
            fn($event) => var_dump(get_debug_type($event))
        );
        $ref = $system->root()->spawnNamed(
            ActorSystem\Props::fromProducer(fn() => new Actor($shopperId)),
            "shopper:$shopperId"
        );
        $system->root()->send(
            $ref->getRef(),
            new AddItem(
                Item::create('Apple Macbook Pro', 1, 2499), $shopperId
            )
        );
        $system->root()->send($ref->getRef(), new PayBasket($shopperId));
    });
});
