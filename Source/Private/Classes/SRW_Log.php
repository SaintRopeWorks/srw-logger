<?php

declare(strict_types=1);

namespace SRW\Logger\Private\Classes;

use SRW\Logger\Public\Classes\SRW_Logger;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;
use SRW\Logger\Private\Enumerations\SRW_Log_Type;
use SRW\Logger\Public\Classes\SRW_CallStackFrame;

class SRW_Log
{
    public string $Name;
    public string $Path;
    public bool $IsFamily;

    public function __construct(string $n, string $p, bool $i, bool $append ) {
        $this->IsFamily = $i;
        $this->Name = str_replace(['<', '>', '"', "'"], '_', $n);
        $this->Path = $p . '/' . $this->Name . '.log';
        if (!$append && file_exists($this->Path))
            @unlink($this->Path);
    }

    private function TestAndRollOver(): void {
        if (file_exists($this->Path) && filesize($this->Path) >= SRW_Logger::$RollOverSize) {
            $timestamp = date('Ymd-His');
            $newPath = str($this->Path)->replace('.log', "-{$timestamp}.log")->toString();
            rename($this->Path, $newPath);
        }
    }

    public function Write(
        string $Message, 
        mixed $Data = null, 
        SRW_Log_Level $EntryLevel = SRW_Log_Level::Information, 
        ?string $Component = null
    ): string {
        $isSignature1 = ($Component === null);
        $activeComponent = $Component ?? $this->Name;
        $this->TestAndRollOver();
        $sanitizedMessage = str_replace('"', '`"', $Message);
        $callFrame = SRW_Logger::GetCall();
        $messageBlock = $this->NewLogLine($sanitizedMessage, SRW_Log_Type::Message, $EntryLevel, $activeComponent, $callFrame);
        file_put_contents($this->Path, $messageBlock, FILE_APPEND);

        if ($Data !== null) {
            $json = json_encode($Data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                $json = json_encode([
                    '__serialization_error' => 'Unable to serialize log data payload',
                    'raw_type' => gettype($Data),
                    'error_message' => json_last_error_msg()
                ], JSON_PRETTY_PRINT);
            }
            $dataBlock = $this->NewLogLine($json, SRW_Log_Type::Data, $EntryLevel, $activeComponent, $callFrame);
            file_put_contents($this->Path, $dataBlock, FILE_APPEND);
        }
        if (!$this->IsFamily) {
            try{
                SRW_Logger::Family()->each(function (SRW_Log $FamilyLog) use ($Message, $Data, $EntryLevel): void {
                    $FamilyLog->Write($Message, $Data, $EntryLevel, $this->Name);
                });
            } catch (\Throwable $e) {
                error_log(sprintf(
                    'SRW_Logger: family log fan-out failed for "%s": %s',
                    $this->Name,
                    $e->getMessage()
                ));
            }
        }
        if ($isSignature1) {
            SRW_Logger::ClearCalls();
        }
        return $Message;
    }

    private function NewLogLine(
        string $Entry, 
        SRW_Log_Type $Type, 
        SRW_Log_Level $EntryLevel, 
        string $Component, 
        ?SRW_CallStackFrame $Caller
    ): string {
        $prefix = ($Type === SRW_Log_Type::Data) ? "\t" : "";
        $maxMessageSize = SRW_Logger::$MaxMessageSize;
        $chunks = str_split($Entry, $maxMessageSize);
        $output = "";
        $schemaPattern = SRW_Logger::GetLogSchema();
        $siteName = (function_exists('get_bloginfo') && !isset($GLOBALS['srw_mock_cli_mode']))
            ? get_bloginfo('name')
            : 'WordPress';
        $basename = $Caller ? $Caller->Basename : 'index.php';
        $lineNum  = $Caller ? $Caller->Line : 0;
        $funcMeta = $Caller ? " -> {$Caller->ResolvedFunction}" : " -> {$Component}()";
        $contextString = "{$basename}:{$lineNum}{$funcMeta}";
        foreach ($chunks as $index => $chunk) {
            if (count($chunks) > 1 && $index < count($chunks) - 1) {
                $chunk = str_pad($chunk, $maxMessageSize + 3, ".");
            }
            $line = $schemaPattern;
            // Swap standard structural string values
            $line = str_replace('{SITENAME}', $siteName, $line);
            $line = str_replace('{LEVEL}', (string)$EntryLevel->value, $line); // Outputs pure integer compliant value
            $line = str_replace('{CONTEXT}', $contextString, $line);
            $line = str_replace('{MESSAGE}', "{$prefix}{$chunk}", $line);
            $line = preg_replace_callback('/\{DATE:([^}]+)\}/', fn($m) => date($m[1]), $line);
            $line = preg_replace_callback('/\{TIME:([^}]+)\}/', function ($m) {
                $timeFormat = $m[1];
                if (str_contains($timeFormat, 'v')) {
                    $ms = sprintf('%03d', (int)(microtime(true) * 1000) % 1000);
                    return date(str_replace('v', $ms, $timeFormat));
                }
                return date($timeFormat);
            }, $line);
            $output .= $line . PHP_EOL;
        }
        return $output;
    }
}