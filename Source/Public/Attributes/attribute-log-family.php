<?php

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class LogFamily {
    public string $family;
    public ?string $path;
    public string $resolver;

    public function __construct(string $family, ?string $path = null, string $resolver = 'scalar') {
        $this->family = $family;
        $this->path = $path;
        $this->resolver = $resolver;
    }
}
