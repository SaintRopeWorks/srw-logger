<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Contracts;

use SRW\Logger\Private\Classes\SRW_Log_Entry_Context;

interface LogSchemaInterface {
    /** Append a token to the end of the schema. */
    public function push(LogSchemaTokenInterface $token): static;

    /** @return LogSchemaTokenInterface[] Tokens in render order. */
    public function tokens(): array;

    /** Resolve every token against $context and concatenate them into one physical line (no trailing newline). */
    public function build(SRW_Log_Entry_Context $context): string;

    /**
     * Serializable representation for persistence (e.g. wp_options), and
     * for handing to the admin-wizard JS. One entry per token:
     * ['token' => <name>, 'format' => <format>].
     */
    public function toTemplateArray(): array;
}