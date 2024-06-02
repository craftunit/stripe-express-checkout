<?php

namespace craftunit\craftstripeexpresscheckout\traits;

/**
 * @method static cases()
 */
trait AsOptions
{
    public static function asOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->name;
        }
        return $options;
        // return array_map(static fn($case) => ['label' => $case->name, 'value' => $case->value], $cases);
    }

    public static function asValues(): array
    {
        return array_map(static fn($case) => $case->value, self::cases());
    }
}
