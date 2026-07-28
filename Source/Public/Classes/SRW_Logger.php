<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Public\Classes;

use SRW\Logger\Private\Classes\SRW_Log;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;
use SRW\Logger\Private\Enumerations\SRW_Log_Type;
use SRW\Logger\Public\Attributes\LogFamily;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionException;

class SRW_Logger {
    public static string $path = 
        '';
    public static bool $append = 
        true;
    public static int $roll_over_size = 
        2097152; // 2MB Default
    public static int $max_message_size = 
        5000;

    private static ?array $_call_stack = 
        null;
    private static array $_logger_registry = 
        [];
    private static string $format_string = 
        '';

    public static function setup(
        ?string $custom_dir = 
            null, 
        bool $append = 
            true, 
        int $roll_size = 
            2097152, 
        int $max_msg_size = 
            5000
    ) {
        self
            ::$append = 
                $append;
        self
            ::$roll_over_size = 
                $roll_size;
        self
            ::$max_message_size = 
                $max_msg_size;
        if ( 
            !empty( 
                $custom_dir 
            ) 
        ) {
            self::
                $path = 
                    $custom_dir;
        } else {
            $wp_upload = 
                function_exists( 
                    'wp_upload_dir' 
                ) 
                    ? wp_upload_dir(                        
                    ) 
                    : null;
            if ( 
                $wp_upload && 
                    isset( 
                        $wp_upload[
                            'basedir'
                        ] 
                    ) 
            ) {
                self::
                    $path = 
                        $wp_upload[
                            'basedir'
                        ] 
                            . '/srw-enterprise-logs';
            } else {
                self::
                    $path = 
                        sys_get_temp_dir(                            
                        ) 
                            . '/srw-enterprise-logs';
            }
        }
        self
            ::$format_string = 
                function_exists(
                    'get_option'
                ) 
                    ? get_option(
                        'srw_log_format_string', 
                        '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}') 
                    : '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}';
        self
            ::create_path(
                self
                    ::$path
            );
        self
            ::purge_old_logs(
            );
    }

    public static function get_max_message_size(        
    ): int { 
        return self::
            $max_message_size; 
    }
    public static function get_roll_over_size(
    ): int { 
        return self::
            $roll_over_size; 
    }
    public static function get_format_string(
    ): string {
        return (
            empty(
                self
                    ::$format_string
            )
        )
            ? '[{DATE:Y-m-d} {TIME:H:i:s.v}] [{LEVEL}] [{CONTEXT}] | {MESSAGE}'
            : self
                ::$format_string;
    }    

    public static function info(
        string $message, 
        $data = 
            null
    ) { 
        self
            ::get(
            )
                ->write(
                    $message, 
                    $data, 
                    SRW_Log_Level
                        ::Info
                ); 
    }
    public static function warn(
        string $message, 
        $data = 
            null
    ) { 
        self
            ::get(
            )
                ->write(
                    $message, 
                    $data, 
                    SRW_Log_Level
                        ::Warn
                ); 
    }
    public static function error(
        string $message, 
        $data = 
            null
    ) { 
        self
            ::get(
            )
                ->write(
                    $message, 
                    $data, 
                    SRW_Log_Level
                        ::Error
                ); 
    }    
    public static function verbose(
        string $message, 
        $data = 
            null
    ) { 
        self
            ::get(
            )
                ->write(
                    $message, 
                    $data, 
                    SRW_Log_Level
                        ::Verbose
                ); 
    }

    private static function get(
        bool $force = 
            false
    ): SRW_Log {
        return self
            ::get_log(
                self
                    ::get_call_name(
                    ), 
                self::$path, 
                self::$append, 
                $force, 
                false
            );
    }

    public static function clear_calls(
    ) {
        self
            ::$_call_stack = 
                null;
    }

    private static function get_calls(
    ): array {
        self
            ::$_call_stack = 
                self
                    ::$_call_stack ??
                        [
                            isset(
                                $GLOBALS[
                                    'srw_mock_caller_context'
                                ]
                            )
                                ? $GLOBALS[
                                    'srw_mock_caller_context'
                                ]
                                : self
                                    ::get_caller_context(
                                    )
                        ];
        return self
            ::$_call_stack;
    }
    
    public static function get_current_call(
    ): ?array {
        $calls = 
            self
                ::get_calls(
                );
        return !empty(
            $calls
        ) && 
            isset(
                $calls[
                    0
                ]
            )
            ? $calls[
                0
            ] 
            : null;
    }

    private static function get_call_name(
    ): string {
        $call = 
            self
                ::get_current_call(
                );
        return $call 
            ? $call[
                'target_name'
            ] 
            : 'global_scope';
    }

    public static function family(        
    ): array {
        $call = 
            self
                ::get_current_call(
                );
        if (
            !$call 
                || empty(
                    $call[
                        'families'
                    ]
                )
        )
            return [
            ];
        $defined_family_logs = 
            [
            ];
        foreach (
            $call[
                'families'
            ] as 
                $family_meta
        ) {
            $target_path =
                (
                    !empty(
                            $family_meta[
                                'path'
                            ]
                    ) 
                        && !str_starts_with(
                            $family_meta[
                                'path'
                            ], 
                            '/'
                        ) 
                            && !str_contains(
                                $family_meta[
                                    'path'
                                ], 
                                ':'
                            )
                ) 
                    ? self
                        ::$path 
                            . '/' 
                            . trim(
                                $family_meta[
                                    'path'
                                ], 
                                '/'
                            )
                    : $family_meta[
                        'path'
                    ] ?? 
                        self
                            ::$path;
            $defined_family_logs[
            ] = 
                self
                    ::get_log(
                        $family_meta[
                            'name'
                        ], 
                        $target_path, 
                        true, 
                        false, 
                        true
                    );
        }
        return $defined_family_logs;
    }    

    public static function get_log(
        string $name, 
        string $path, 
        bool $append, 
        bool $force, 
        bool $is_family
    ): SRW_Log {
        $registry_key = 
            $path 
                . '/' 
                . $name;
        if (
            !isset(
                self
                    ::$_logger_registry[
                        $registry_key
                    ]
            ) 
                || $force
        ) {
            self
                ::$_logger_registry[
                    $registry_key
                ] = 
                    new SRW_Log(
                        $name, 
                        $path, 
                        $is_family, 
                        $append
                    );
        }
        self
            ::create_path(
                $path
            );
        return self
            ::$_logger_registry[
                $registry_key
            ];
    }    

    public static function create_path(
        string $path
    ) {
        if (
            !file_exists(
                $path
            )
        ) {
            mkdir(
                $path, 
                0755, 
                true
            );
            if (
                str_contains(
                    $path, 
                    'wp-content/uploads'
                )
            ) {
                @file_put_contents(
                    $path 
                        . '/.htaccess', 
                    "Order Deny,Allow" 
                        . PHP_EOL 
                        . "Deny from all"
                );
                @file_put_contents(
                    $path 
                        . '/index.html', 
                    ''
                );
            }
        }
    }   

    private static function purge_old_logs(
    ) {
        $days_to_keep = 
            function_exists(
                'get_option'
            ) 
                ? (int)get_option(
                    'srw_log_retention_days', 
                    14
                ) 
                : 14;
        if (
            $days_to_keep <= 
                0
        ) { 
            $days_to_keep = 
                14; 
        }
        $max_age_seconds = 
            $days_to_keep * 
                86400;
        $current_time = 
            time(
            );
        $log_files = 
            glob(
                self
                    ::$path 
                        . '/*-[0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9]-*.log'
            );
        if (
            !empty(
                $log_files
            )
        ) {
            foreach (
                $log_files as 
                    $file
            ) {
                if (
                    is_file(
                        $file
                    ) 
                        && (
                            $current_time - 
                                filemtime(
                                    $file
                                )
                        ) > 
                            $max_age_seconds
                ) {
                    @unlink(
                        $file
                    );
                }
            }
        }
    }

    private static function get_caller_context(
    ): array {
        $trace = 
            debug_backtrace(
                DEBUG_BACKTRACE_IGNORE_ARGS
            );
        static $attribute_cache = 
            [
            ];        
        $resolved_function = 
            'global_scope';
        $target_name = 
            'global_scope';
        $basename = 
            isset(
                $_SERVER[
                    'SCRIPT_FILENAME'
                ]
            ) 
                ? basename(
                    $_SERVER[
                        'SCRIPT_FILENAME'
                    ]
                ) 
                : 'index.php';
        $line = 
            0;
        
        foreach (
            $trace as 
                $index => 
                    $frame
        ) {
            if (
                isset(
                    $frame[
                        'class'
                    ]
                ) 
                    && $frame[
                        'class'
                    ] === 
                        self
                            ::class
            )
                continue; 
            if (
                $line === 
                    0
            ) {
                $file = 
                    $frame[
                        'file'
                    ] ?? 
                        'global';
                $basename = 
                    basename(
                        $file
                    );
                $line = 
                    $frame[
                        'line'
                    ] ?? 
                        0;
            }

            $current_fn = 
                $frame[
                    'function'
                ] ?? 
                    '';
            if (
                empty(
                    $current_fn
                ) || 
                    str_contains(
                        $current_fn, 
                        '{closure}'
                    ) || 
                        in_array(
                            $current_fn, 
                            [
                                'include', 
                                'include_once', 
                                'require', 
                                'require_once', 
                                'array_map', 
                                'array_filter', 
                                'call_user_func', 
                                'call_user_func_array'
                            ]
                        )
            )
                continue;
            if (
                isset(
                    $frame[
                        'class'
                    ]
                )
            ) {
                $short_class = 
                    basename(
                        str_replace(
                            '\\', 
                            '/', 
                            $frame[
                                'class'
                            ]
                        )
                    );
                $resolved_function = 
                    "{$short_class}::{$current_fn}()";
                $target_name = 
                    "{$short_class}__{$current_fn}";
            } else {
                $resolved_function = 
                    "{$current_fn}()";
                $target_name = 
                    $current_fn;
            }
            break;
        }

        $discovered_families = 
            [
            ];
        if (
            $target_name !== 
                'global_scope' && 
                    !empty(
                        $target_name
                    )
        ) {
            if (
                isset(
                    $attribute_cache[
                        $target_name
                    ]
                )
            ) {
                $discovered_families = 
                    $attribute_cache[
                        $target_name
                    ];
            } else {
                try {
                    if (
                        isset(
                            $frame[
                                'class'
                            ]
                        )
                    ) {
                        $reflector = 
                            new ReflectionMethod(
                                $frame[
                                    'class'
                                ], 
                                $current_fn
                            );
                    } else {
                        $reflector = 
                            new ReflectionFunction(
                                $current_fn
                            );
                    }
                    $discovered_families = 
                        self::
                            extract_families_from_reflector(
                                $reflector
                            );
                    $attribute_cache[
                        $target_name
                    ] = 
                        $discovered_families;
                } catch (
                    ReflectionException $e
                ) {
                }
            }
        }

        return [
            'target_name' => 
                self::
                    sanitize(
                        $target_name
                    ),
            'basename' => 
                $basename,
            'line' => 
                $line,
            'resolved_function' => 
                $resolved_function,
            'families' => 
                $discovered_families
        ];
    }

    private static function extract_families_from_reflector(
        $reflector
    ): array {
        $families = 
            [
            ];
        $attributes = 
            $reflector->
                getAttributes(
                    LogFamily::
                        class
                );
        foreach (
            $attributes as 
                $attribute
        ) {
            $instance = 
                $attribute->
                    newInstance(
                    );
            $resolved_name = 
                $instance->
                    family;

            $resolved_name = 
                match(
                    $instance->resolver
                ) {
                    'global' => 
                        (string)(
                            $GLOBALS[
                                $resolved_name
                            ] ??
                                $resolved_name
                        ),
                    'option' =>
                        (string)get_option(
                            $resolved_name, 
                            $resolved_name
                        ),
                    'constant' =>
                        defined(
                            $resolved_name
                        ) 
                            ?  (string)constant(
                                $resolved_name
                            )
                            : $resolved_name,
                    default => 
                        $resolved_name
            };
            if (
                !empty(
                    $resolved_name
                )
            )  
                $families[
                ] = 
                    [
                        'name' => 
                            self::
                                sanitize(
                                    $resolved_name
                                ), 
                        'path' => 
                            $instance->
                                path
                    ];             
        }
        return $families;
    }

    private static function sanitize(
        string $name
    ): string {
        return sanitize_file_name(
            str_replace(
                [
                    '::', 
                    '\\', 
                    '/', 
                    '{', 
                    '}', 
                    '__'
                ], 
                '_', 
                $name
            )
        );
    }
}