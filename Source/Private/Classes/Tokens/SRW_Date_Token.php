<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Classes\Tokens;

use SRW\Logger\Private\Contracts\LogSchemaTokenInterface;
use SRW\Logger\Private\Classes\SRW_Log_Entry_Context;

final class SRW_Date_Token implements LogSchemaTokenInterface {
    public function __construct(private string $format = 'Y-m-d') {}

    public function getName(): string { return 'DATE'; }

    public function getDisplayMeta(): array {
        return [
            'label'    => 'Date',
            'icon'     => '⏰',
            'category' => 'temporal',
        ];
    }

    public function getOptions(): array {
        return ['format' => $this->format];
    }

    public function getOptionSchema(): array {
        return [
            [
                'key'     => 'format',
                'label'   => 'Date Format',
                'type'    => 'select',
                'choices' => [
                    ['value' => 'Y-m-d', 'label' => 'YYYY-MM-DD (e.g. 2026-07-18)'],
                    ['value' => 'Y-M-d', 'label' => 'YYYY-MMM-DD (e.g. 2026-Jul-18)'],
                    ['value' => 'm/d/Y', 'label' => 'MM/DD/YYYY (e.g. 07/18/2026)'],
                    ['value' => 'd-m-Y', 'label' => 'DD-MM-YYYY (e.g. 18-07-2026)'],
                ],
            ],
        ];
    }

    public function isParameterized(): bool { return true; }

    public function withOptions(array $options): static {
        return new static($options['format'] ?? $this->format);
    }

    public function getValue(): \Closure {
        return fn(SRW_Log_Entry_Context $context): string =>
            $context->Timestamp->format($this->format);
    }
}