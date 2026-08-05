<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Contracts;

interface LogSchemaTokenInterface {
    /** Machine-stable identifier, e.g. "DATE", "SITENAME", "USER_STRING". */
    public function getName(): string;

    /** Metadata the admin wizard needs to render this as a chip: label, icon, category, etc. */
    public function getDisplayMeta(): array;

    /**
     * This instance's current configuration, keyed by option name.
     * e.g. Time: ['format' => 'H:i:s.v', 'timezone' => 'America/New_York'].
     * Empty array if not parameterized.
     */
    public function getOptions(): array;

    /**
     * Describes every configurable option for this token TYPE, for the
     * admin UI to render pickers from - one entry per option:
     * ['key' => 'format', 'label' => 'Time Format', 'choices' => [['value' => ..., 'label' => ...], ...]].
     * A token can declare as many independent options as it needs (Time
     * needs format + timezone; most tokens need none). Empty array if not
     * parameterized.
     */
    public function getOptionSchema(): array;

    /** Whether this token type has any configurable options at all - drives whether the admin UI offers a picker. */
    public function isParameterized(): bool;

    /** Returns a new instance of this token with the given option overrides merged into its current options. */
    public function withOptions(array $options): static;

    /**
     * Closure that resolves this token's rendered value for one physical
     * line, given that line's context.
     *
     * @return \Closure(\SRW\Logger\Private\Classes\SRW_Log_Entry_Context): string
     */
    public function getValue(): \Closure;
}