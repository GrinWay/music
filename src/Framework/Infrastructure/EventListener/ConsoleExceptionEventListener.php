<?php

namespace App\Framework\Infrastructure\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('kernel.event_listener')]
class ConsoleExceptionEventListener
{
    public function __construct(
        private readonly LoggerInterface $phpLogger,
    ) {
    }

    public function __invoke(ConsoleErrorEvent $event): void
    {
        $throwable = $event->getError();
        $message = $throwable->getMessage();
        $context = ['trace' => $throwable->getTraceAsString()];
        $this->phpLogger->critical($message, $context);
    }
}
