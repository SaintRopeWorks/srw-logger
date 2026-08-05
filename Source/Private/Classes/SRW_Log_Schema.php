<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Classes;

use SRW\Logger\Private\Contracts\LogSchemaInterface;
use SRW\Logger\Private\Contracts\LogSchemaTokenInterface;

final class SRW_Log_Schema implements LogSchemaInterface {
    /** @var LogSchemaTokenInterface[] */
    private array $tokens = [];

    /** @param LogSchemaTokenInterface[] $tokens */
    public function __construct(array $tokens = []) {
        foreach ($tokens as $token) {
            $this->push($token);
        }
    }

    public function push(LogSchemaTokenInterface $token): static {
        $this->tokens[] = $token;
        return $this;
    }

    public function tokens(): array {
        return $this->tokens;
    }

    public function build(SRW_Log_Entry_Context $context): string {
        $line = '';
        foreach ($this->tokens as $token) {
            $line .= ($token->getValue())($context);
        }
        return $line;
    }

    public function toTemplateArray(): array {
        return array_map(
            fn(LogSchemaTokenInterface $token) => [
                'token'   => $token->getName(),
                'options' => $token->getOptions(),
            ],
            $this->tokens
        );
    }
}