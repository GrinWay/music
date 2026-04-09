<?php

namespace App\Framework\Infrastructure\EnvProcessor;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

class Base64EncodeEnvProcessor implements EnvVarProcessorInterface
{
    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        $string = $getEnv($name);

        return \base64_encode($string);
    }

    public static function getProvidedTypes(): array
    {
        return [
            'app_base64_encode' => 'string',
        ];
    }
}
