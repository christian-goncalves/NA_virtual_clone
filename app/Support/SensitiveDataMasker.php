<?php

namespace App\Support;

class SensitiveDataMasker
{
    public static function sanitizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $sanitized = preg_replace(
            [
                '/(password|senha|passcode)\s*[:=]\s*([^\s,;]+)/iu',
                '/(token|api[_-]?key|secret|authorization)\s*[:=]\s*([^\s,;]+)/iu',
                '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i',
                '/([?&](?:token|key|password|senha|secret)=)[^&\s]+/iu',
            ],
            [
                '$1=[redacted]',
                '$1=[redacted]',
                'Bearer [redacted]',
                '$1[redacted]',
            ],
            $value
        );

        return is_string($sanitized) ? trim($sanitized) : trim($value);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::sanitizeText($value);

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeContext($value);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
