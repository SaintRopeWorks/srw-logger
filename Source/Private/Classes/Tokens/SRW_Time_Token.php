<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Classes\Tokens;

use SRW\Logger\Private\Contracts\LogSchemaTokenInterface;
use SRW\Logger\Private\Classes\SRW_Log_Entry_Context;

final class SRW_Time_Token implements LogSchemaTokenInterface {
    public function __construct(
        private string $format = 'H:i:s.v',
        private ?string $timezone = null, // null = resolve the site/server default at render time
    ) {}

    public function getName(): string { return 'TIME'; }

    public function getDisplayMeta(): array {
        return [
            'label'    => 'Time',
            'icon'     => '⏳',
            'category' => 'temporal',
        ];
    }

    public function getOptions(): array {
        return [
            'format'   => $this->format,
            'timezone' => $this->timezone ?? self::defaultTimezone(),
        ];
    }

    public function getOptionSchema(): array {
        return [
            [
                'key'     => 'format',
                'label'   => 'Time Format',
                'type'    => 'select',
                'choices' => [
                    ['value' => 'H:i:s.v', 'label' => 'HH:MM:SS.mmm (24hr + Milliseconds)'],
                    ['value' => 'H:i:s', 'label' => 'HH:MM:SS (24hr Default)'],
                    ['value' => 'h:i:s a', 'label' => 'HH:MM:SS am/pm (12hr format)'],
                ],
            ],
            [
                'key'     => 'timezone',
                'label'   => 'Timezone',
                // 400+ IANA identifiers - the admin UI almost certainly
                // wants a searchable/native <select> rather than the
                // small dropdown pattern format uses, but the data itself
                // can come straight from PHP either way.
                'type'    => 'select',
                'choices' => self::timezoneChoices(),
            ],
        ];
    }

    public function isParameterized(): bool { return true; }

    public function withOptions(array $options): static {
        return new static(
            $options['format'] ?? $this->format,
            $options['timezone'] ?? $this->timezone,
        );
    }

    public function getValue(): \Closure {
        $timezone = $this->timezone ?? self::defaultTimezone();
        $format = $this->format;
        return function (SRW_Log_Entry_Context $context) use ($timezone, $format): string {
            // setTimezone() returns a new instance representing the SAME
            // instant, just displayed through a different offset - this is
            // what lets two SRW_Time_Token instances in the same schema
            // show different timezones without disagreeing about "now".
            return $context->Timestamp
                ->setTimezone(new \DateTimeZone($timezone))
                ->format($format);
        };
    }

    private static function defaultTimezone(): string {
        if (function_exists('wp_timezone_string') && !isset($GLOBALS['srw_mock_cli_mode'])) {
            return wp_timezone_string();
        }
        return date_default_timezone_get() ?: 'UTC';
    }

    private static function timezoneChoices(): array {
        return array_map(
            fn(string $tz) => ['value' => $tz, 'label' => $tz],
            \DateTimeZone::listIdentifiers()
        );
    }
}