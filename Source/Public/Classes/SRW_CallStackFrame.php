<?php
declare(strict_types = 1);

namespace SRW\Logger\Public\Classes;

class SRW_CallStackFrame {
    public string $File;
    public int $Line;
    public string $Basename;
    public ?string $Class;
    public string $Function;
    public ?string $CallType;
    public string $ResolvedFunction;
    public string $TargetName;

    public function __construct(array $Frame,?array $NextFrame) {
        $this->File = $Frame['file'] ?? 'global';
        $this->Line = $Frame['line'] ?? 0;
        $this->Function = $NextFrame['function'] ?? 'global_scope';

        if (
            $this->Line === 0 && 
            str_contains($Frame['function'] ?? '', '{closure')
        ) {
            // Regex matches numbers following a colon, focusing on the trailing ones
            if (preg_match_all('/:(\d+)/', $this->Function, $matches)) {
                $allNumbers = $matches[1];
                // Grab the very last (rightmost / closest) number from the matching list
                $this->Line = (int)end($allNumbers);
            }
        }
        
        $this->Basename = isset($Frame['file']) 
            ? basename($Frame['file']) 
            : 'global';

        $this->Class = $NextFrame['class'] ?? null;        
        $this->CallType = $NextFrame['type'] ?? null; 

        if ($this->Class !== null) {
            $shortClass = basename(
                str_replace('\\', '/', $this->Class)
            );
            $this->ResolvedFunction = 
                "{$shortClass}{$this->CallType}{$this->Function}()";
            $this->TargetName = 
                "{$shortClass}__{$this->Function}";
        } else {
            $this->ResolvedFunction = "{$this->Function}()";
            $this->TargetName = $this->Function;
        }
    }
}
