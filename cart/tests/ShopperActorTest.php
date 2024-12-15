<?php

declare(strict_types=1);

use Cart\Basket\Command\AddItem;
use Cart\InMemoryStateProvider;
use Cart\Item;
use Cart\Shopper\Actor;
use Cart\Shopper\ActorTwo;
use Cart\Shopper\Command\PayBasket;
use Cart\Shopper\Value\Cash;
use Phluxor\ActorSystem;
use Phluxor\ActorSystem\Props;
use Phluxor\ActorSystem\Ref;
use Phluxor\Persistence\EventSourcedBehavior;
use Phluxor\Persistence\InMemoryProvider;
use Phluxor\Persistence\ProviderInterface;
use PHPUnit\Framework\TestCase;

use function Swoole\Coroutine\go;
use function Swoole\Coroutine\run;

class ShopperActorTest extends TestCase
{
    private string $shopperId = '1';
    private ProviderInterface $provider;

    protected function setUp(): void
    {
        $this->provider = $this->stateProvider();
    }

    public function testPaid(): void
    {
        run(function () {
            go(function () {
                $shopperId = 1;
                $system = ActorSystem::create();
                $system->getEventStream()->subscribe(
                    fn($event) => var_dump(get_debug_type($event))
                );
                $ref = $system->root()->spawnNamed(
                    Props::fromProducer(fn() => new Actor($shopperId)),
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
    }

    public function testActorTwo(): void
    {
        run(function () {
            go(function () {
                $shopperId = 1;
                $system = ActorSystem::create();
                $system->getEventStream()->subscribe(
                    fn($event) => var_dump(get_debug_type($event))
                );
                $ref = $system->root()->spawnNamed(
                    Props::fromProducer(
                        fn() => new ActorTwo(
                            $shopperId,
                            $this->spawnBasket($system->root()),
                            $this->spawnWallet($system->root())
                        )
                    ),
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
    }

    private function spawnWallet(
        ActorSystem\RootContext $context
    ): ?Ref {
        $spawned = $context->spawnNamed(
            Props::fromProducer(
                fn() => new \Cart\Wallet\Actor((string)Cash::VALUE),
                Props::withReceiverMiddleware(
                    new EventSourcedBehavior($this->provider)
                )
            ),
            "wallet:$this->shopperId"
        );
        if ($spawned->isError()) {
            $context->logger()->error($spawned->isError()->getMessage());
            return null;
        }
        return $spawned->getRef();
    }

    private function spawnBasket(
        ActorSystem\RootContext $context
    ): ?Ref {
        $spawned = $context->spawnNamed(
            Props::fromProducer(
                fn() => new \Cart\Basket\Actor(),
                Props::withReceiverMiddleware(
                    new EventSourcedBehavior($this->provider)
                )
            ),
            "basket:$this->shopperId"
        );
        if ($spawned->isError()) {
            $context->logger()->error($spawned->isError()->getMessage());
            return null;
        }
        return $spawned->getRef();
    }

    private function stateProvider(): InMemoryStateProvider
    {
        return new InMemoryStateProvider(new InMemoryProvider(1));
    }
}
