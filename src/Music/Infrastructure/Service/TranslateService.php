<?php

namespace App\Music\Infrastructure\Service;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsDecorator('translator.default')]
class TranslateService implements TranslatorInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->translator->{$name}(...$arguments);
    }
}
