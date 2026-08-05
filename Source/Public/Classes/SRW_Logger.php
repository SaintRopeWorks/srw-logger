<?php

declare(strict_types = 1);

namespace SRW\Logger\Public\Classes;

use Illuminate\Support\Collection;
use SRW\Logger\Private\Classes\SRW_Log;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;
use SRW\Logger\Public\Classes\SRW_CallStackFrame;
use SRW\Logger\Private\Enumerations\SRW_Log_Type;
use SRW\Logger\Public\Attributes\LogFamily;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;

class SRW_Logger {
    public static string $Path = '';
    public static bool $Append = true;
    public static int $RollOverSize = 2097152; // 2MB Default
    public static int $MaxMessageSize = 5000;

    /**
     * @var Collection<int, SRW_CallStackFrame>|null
     */
    private static ?Collection $_CallStack = null;
    private static array $_Logger = [];
    private static string $LogSchema = '';

    public static function Logger(): void {
        self::ClearCalls();
        self::Setup();
    }

    public static function Setup(
        ?string $Path = null, 
        bool $Append = true, 
        int $RollOverSize = 2097152, 
        int $MaxMessageSize = 5000
    ) {
        self::$Append = $Append;
        self::$RollOverSize = $RollOverSize;
        self::$MaxMessageSize = $MaxMessageSize;
        if ( !empty( $Path ) ) {
            self::$Path = $Path;
        } else {
            $wp_upload = (function_exists('wp_upload_dir') && !isset($GLOBALS['srw_mock_cli_mode'])) ? wp_upload_dir() : null;
            self::$Path = ( $wp_upload && isset($wp_upload['basedir'])) ? $wp_upload['basedir'] : sys_get_temp_dir() . '/srw-enterprise-logs';
        }
        self::$LogSchema = (function_exists('get_option') && !isset($GLOBALS['srw_mock_cli_mode']))
            ? get_option('srw_log_format_string', '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}') 
            : '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}';
        self::CreatePath(self::$Path);
        self::PurgeOldLogs();
    }

    public static function GetLogSchema(): string { return (empty(self::$LogSchema)) ? '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}' : self::$LogSchema; }    
    public static function SetLogSchema(string $format): void { self::$LogSchema = $format; }

    public static function Information( string $Message, $Data = null ): string {
         return self::Get()->
            Write($Message, $Data, SRW_Log_Level::Information);
    }
    public static function Warning( string $Message, $Data = null ): string { 
        return self::Get()->
            Write($Message, $Data, SRW_Log_Level::Warning); 
    }
    public static function Error(string $Message, $Data = null ): string { 
        return self::Get()->
            Write($Message, $Data, SRW_Log_Level::Error); 
    }    
    public static function Verbose(string $Message, $Data = null): string { 
        return self::Get()->
            Write($Message, $Data, SRW_Log_Level::Verbose); 
    }

    private static function Get( bool $force = false ): SRW_Log { 
        return self::GetLog(self::GetCallName(),self::$Path,self::$Append,$force,false); 
    }

    private static function GetLog( string $Name,string $Path, bool $Append, bool $Force, bool $IsFamily): SRW_Log {
        if (!isset(self::$_Logger[$Name]) || $Force)
            self::$_Logger[$Name] = 
                new SRW_Log($Name, $Path, $IsFamily, $Append);
        self::CreatePath($Path);
        return self::$_Logger[$Name];
    }

    public static function ClearCalls() { 
        self::$_CallStack = null; 
    }
    
    private static function GetCalls(): Collection {
        if (self::$_CallStack !== null) 
            return self::$_CallStack;
        if (isset($GLOBALS['srw_mock_caller_context'])) {
            self::$_CallStack = 
                collect([$GLOBALS['srw_mock_caller_context']]);
            return self::$_CallStack;
        }
        $rawTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $totalFrames = count($rawTrace);
        $sliceIndex = 0;
        for ($i = 0; $i < $totalFrames; $i++) {
            $file = $rawTrace[$i]['file'] ?? '';
            if (str($file)->contains('/Logger/Source')) {
                $sliceIndex = $i + 1;
                continue;
            }
            break;
        }
        $slicedTrace = array_values(
            array_slice($rawTrace, $sliceIndex)
        );        
        self::$_CallStack = collect($slicedTrace)->
            map(
                function (
                    array $currentFrame, int $index
                ) use ($slicedTrace): SRW_CallStackFrame {
                    return new SRW_CallStackFrame(
                        $currentFrame, 
                        $slicedTrace[$index + 1] ?? null
                    );
                }
            )->skipWhile(
                fn(SRW_CallStackFrame $Frame) => 
                    str_contains($Frame->Function, '{closure')
            );
        return self::$_CallStack;
    }
   
    public static function GetCall(): ?SRW_CallStackFrame {
        return self::GetCalls()->first();
    }

    private static function GetCallName(): string {
        $Frame = self::GetCall();
        $Basename = $Frame ? $Frame->Basename : 'global';
        if ($Basename === 'global' || $Basename === '')
            return 'global scope';
        return pathinfo($Basename, PATHINFO_FILENAME);
    }

    public static function Family(): Collection {
        return self::GetFamilies()
            ->push(new LogFamily('DefaultLogFamily'))
            ->unique(fn(LogFamily $Family) => self::GetFamilyName($Family))
            ->map(
                function(LogFamily $Family): SRW_Log {
                    return self::GetLog(
                        self::GetFamilyName($Family),
                        self::GetFamilyPath($Family),
                        true,  
                        false, 
                        true   
                    );
                }
            )
            ->filter(
                fn(SRW_Log $Log) => 
                    $Log->IsFamily
            )
            ->values();
    }    

    private static function GetFamilyPath(LogFamily $Family): string {        
        $path = $Family->path ?? '';
        if (!empty($path) && is_callable($path)) 
            $path = (string)call_user_func($path);
        if (empty($path)) 
            return self::$Path;
        $isRooted = str_starts_with($path, '/') || (str_contains($path, ':') && preg_match('/^[A-Z]:/i', $path));
        return $isRooted ? $path : str(self::$Path)->finish('/')->append($path)->toString();   
    }

    private static function GetFamilyName(LogFamily $Family): string {
        $resolved = $Family->family;
        if ($Family->resolver === 'callable' && is_callable($resolved)) {
            $resolved = (string)call_user_func($resolved);
        } else {
            $resolved = match($Family->resolver) {
                'global'   => (string)($GLOBALS[$resolved] ?? $resolved),
                'option'   => function_exists('get_option') ? (string)get_option($resolved, $resolved) : $resolved,
                'constant' => defined($resolved) ? (string)constant($resolved) : $resolved,
                default    => $resolved
            };
        }
        return self::Sanitize($resolved);
    }

    private static function GetFamilies(): Collection {
        static $attributeCache = [];
        return self::GetCalls()
            ->filter(
                fn(SRW_CallStackFrame $frame) => 
                    !in_array(
                        $frame->Function, 
                        [
                            'include', 
                            'include_once', 
                            'require', 
                            'require_once', 
                            'array_map', 
                            'array_filter', 
                            'call_user_func', 
                            'call_user_func_array'
                        ], 
                        true
                    )
                )
            ->flatMap(
                function (
                    SRW_CallStackFrame $frame
                ) use (&$attributeCache): array {
                    if ($frame->Function === 'global_scope')
                        return [];
                    $cacheKey = $frame->Class 
                        ? "{$frame->Class}::{$frame->Function}" 
                        : $frame->Function;
                    if (isset($attributeCache[$cacheKey])) 
                        return $attributeCache[$cacheKey];
                    try {
                        $reflector = $frame->Class 
                            ? new ReflectionMethod(
                                $frame->Class, $frame->Function
                            ) 
                            : new ReflectionFunction(
                                $frame->Function
                            );
                        $attributes = collect($reflector->getAttributes())
                            ->map(
                                function (\ReflectionAttribute $attr) use ($cacheKey) {
                                    try {
                                        return $attr->newInstance();
                                    } catch (\Throwable $e) {
                                        if ($attr->getName() === 'LogFamily' || str_ends_with($attr->getName(), '\\LogFamily')) {
                                            error_log(sprintf(
                                                'SRW_Logger: found what looks like #[LogFamily] on "%s" (resolved as "%s") but could not instantiate it: %s. Is the SRWLogger plugin loading before this file, so its class_alias() bootstrap has run?',
                                                $cacheKey,
                                                $attr->getName(),
                                                $e->getMessage()
                                            ));
                                        }
                                        return null;
                                    }
                                }
                            )
                            ->filter(fn($instance) => $instance instanceof LogFamily)
                            ->values()
                            ->all();
                        $attributeCache[$cacheKey] = $attributes;
                        return $attributes;
                    } catch (ReflectionException) {
                        $attributeCache[$cacheKey] = [];
                        return [];
                    } catch (\Throwable $e) {
                        $attributeCache[$cacheKey] = [];
                        error_log(sprintf(
                            'SRW_Logger::GetFamilies() failed to reflect on "%s": %s',
                            $cacheKey,
                            $e->getMessage()
                        ));
                        return [];
                    }
                }
            )
            ->unique(
                fn(LogFamily $Family) => 
                    self::GetFamilyName($Family)
            )
            ->values();
    }

    public static function CreatePath(string $path) {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            if (str_contains($path, 'wp-content/uploads')) {
                @file_put_contents($path . '/.htaccess', "Order Deny,Allow" . PHP_EOL . "Deny from all");
                @file_put_contents($path . '/index.html', '');
            }
        }
    }   

    private static function PurgeOldLogs(): void {
        $daysToKeep = (function_exists('get_option') && !isset($GLOBALS['srw_mock_cli_mode'])) 
            ? (int)get_option('srw_log_retention_days', 14) 
            : 14;
            
        if ($daysToKeep <= 0) {
            $daysToKeep = 14; 
        }
        
        $maxAgeSeconds = $daysToKeep * 86400;
        $currentTime = time();
        
        collect((array)glob(self::$Path . '/*.log'))
            ->filter(fn(string $File) => is_file($File) && ($currentTime - filemtime($File)) > $maxAgeSeconds)
            ->each(fn(string $File) => @unlink($File));
    }

    private static function Sanitize(string $name): string { 
        return sanitize_file_name(str_replace(['::','\\','/','{','}', '__'],'_',$name)); 
    }
}

if (!defined('ABSPATH') && php_sapi_name() !== 'cli' && !isset($GLOBALS['srw_mock_cli_mode'])) {
    exit;
}

if (isset($GLOBALS['srw_mock_cli_mode'])) {
    SRW_Logger::Logger();
} elseif (php_sapi_name() === 'cli') {
    SRW_Logger::Logger();
} else {
    add_action('plugins_loaded', [SRW\Logger\Public\Classes\SRW_Logger::class, 'Logger'], 1);
}
