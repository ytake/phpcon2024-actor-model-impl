<?php

declare(strict_types=1);

namespace Cart\Shopper;

use Cart\Basket\Command\Clear;
use Cart\Basket\Command\CommandInterface as BasketCommandInterface;
use Cart\Basket\Command\GetItems;
use Cart\Event\Paid;
use Cart\Items;
use Cart\Shopper\Command\PayBasket;
use Cart\Wallet\Command\CommandInterface as WalletCommandInterface;
use Cart\InMemoryStateProvider;
use Cart\Shopper\Value\Cash;
use Cart\Wallet\Command\Pay;
use Phluxor\ActorSystem\Context\ContextInterface;
use Phluxor\ActorSystem\Message\ActorInterface;
use Phluxor\ActorSystem\Message\Started;
use Phluxor\ActorSystem\Props;
use Phluxor\ActorSystem\Ref;
use Phluxor\Persistence\EventSourcedBehavior;
use Phluxor\Persistence\InMemoryProvider;
use Phluxor\Persistence\ProviderInterface;

class Actor implements ActorInterface
{
    private ?Ref $basketRef = null;
    private ?Ref $walletRef = null;
    private ProviderInterface $provider;

    public function __construct(
        private readonly int $shopperId,
        ?ProviderInterface $provider = null
    ) {
        $this->provider = $provider ?? $this->stateProvider();
    }

    public function receive(ContextInterface $context): void
    {
        $message = $context->message();
        switch (true) {
            case $message instanceof Started:
                $this->basketRef = $this->spawnBasket($context);
                $this->walletRef = $this->spawnWallet($context);
                break;
            case $message instanceof BasketCommandInterface:
                $context->forward($this->basketRef);
                break;
            case $message instanceof WalletCommandInterface:
                $context->forward($this->walletRef);
                break;
            case $message instanceof PayBasket:
                $context->request($this->basketRef, new GetItems($message->shopperId));
                break;
            case $message instanceof Items:
                $context->request($this->walletRef, new Pay($message, $this->shopperId));
                break;
            case $message instanceof Paid:
                $context->send($this->basketRef, new Clear($message->getShopperId()));
                $context->actorSystem()->getEventStream()->publish($message);
                break;
        }
    }

    private function spawnWallet(
        ContextInterface $context
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
        ContextInterface $context
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
