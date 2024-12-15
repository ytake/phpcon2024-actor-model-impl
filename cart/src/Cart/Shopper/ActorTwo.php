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
use Cart\Wallet\Command\Pay;
use Phluxor\ActorSystem\Context\ContextInterface;
use Phluxor\ActorSystem\Message\ActorInterface;
use Phluxor\ActorSystem\Ref;

class ActorTwo implements ActorInterface
{
    public function __construct(
        private readonly int $shopperId,
        private readonly Ref $basketRef,
        private readonly Ref $walletRef
    ) {
    }

    public function receive(ContextInterface $context): void
    {
        $message = $context->message();
        switch (true) {
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
}
