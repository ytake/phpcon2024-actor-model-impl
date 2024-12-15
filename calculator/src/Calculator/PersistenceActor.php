<?php

declare(strict_types=1);

namespace Calculator;

use Calculator\ProtoBuf\Added;
use Calculator\ProtoBuf\Divided;
use Calculator\ProtoBuf\Multiplied;
use Calculator\ProtoBuf\Reset;
use Calculator\ProtoBuf\Subtracted;
use Google\Protobuf\Internal\Message;
use Phluxor\ActorSystem\Context\ContextInterface;
use Phluxor\ActorSystem\Message\ActorInterface;
use Phluxor\Persistence\Message\OfferSnapshot;
use Phluxor\Persistence\Message\RequestSnapshot;
use Phluxor\Persistence\Mixin;
use Phluxor\Persistence\PersistentInterface;

class PersistenceActor implements ActorInterface, PersistentInterface
{
    use Mixin;

    public function __construct(
        private CalculationResult $state = new CalculationResult()
    ) {
    }

    public function receive(ContextInterface $context): void
    {
        $message = $context->message();
        switch (true) {
            case $message instanceof RequestSnapshot:
                $this->persistenceSnapshot($this->state->getProtobufCalculationResult());
                break;
            case $message instanceof Command\Add:
                $this->updateState(new Added(['result' => $message->value]));
                break;
            case $message instanceof Command\Subtract:
                $this->updateState(new Subtracted(['result' => $message->value]));
                break;
            case $message instanceof Command\Multiply:
                $this->updateState(new Multiplied(['result' => $message->value]));
                break;
            case $message instanceof Command\Divide:
                $this->updateState(new Divided(['result' => $message->value]));
                break;
            case $message instanceof Command\Clear:
                $this->updateState(new Reset());
                break;
            case $message instanceof Command\PrintResult:
                $context->logger()->info('CalculationResult is ' . $this->state->getResult());
                break;
            case $message instanceof Command\GetResult:
                $context->respond($this->state->getResult());
                break;
        }
    }

    public function receiveRecover(mixed $message): void
    {
        switch (true) {
            case $message instanceof OfferSnapshot:
                $this->state = new CalculationResult($message->snapshot);
                break;
            case $message instanceof Message:
                $this->updateState($message);
                break;
        }
    }

    private function updateState(Message $message): void
    {
        if (!$this->recovering()) {
            $this->persistenceReceive($message);
        }
        $this->state = match (true) {
            $message instanceof Reset => $this->state->reset(),
            $message instanceof Added => $this->state->add($message->getResult()),
            $message instanceof Subtracted => $this->state->subtract($message->getResult()),
            $message instanceof Divided => $this->state->divide($message->getResult()),
            $message instanceof Multiplied => $this->state->multiply($message->getResult()),
        };
    }
}
