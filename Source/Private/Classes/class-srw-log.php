<?php

declare(
    strict_types =
        1
);

namespace SRW\Logger\Private\Classes;

use SRW\Logger\Public\Classes\SRW_Logger;
use SRW\Logger\Private\Enumerations\SRW_Log_Level;
use SRW\Logger\Private\Enumerations\SRW_Log_Type;

class SRW_Log {
    private string $name;
    private string $path;
    private bool $is_family;

    public function __construct(
        string $name, 
        string $directory, 
        bool $is_family, 
        bool $append
    ) {
        $this->
            is_family = 
                $is_family;
        $this->
            name = 
                str_replace(
                    [
                        '<', 
                        '>', 
                        '"', 
                        "'"
                    ], 
                    '_', 
                    $name
                );
        $this->
            path = 
                $directory 
                    . '/' 
                    . $this->
                        name 
                    . '.log';
        if (
            !$append && 
                file_exists(
                    $this->
                        path
                )
        ) 
            @unlink(
                $this->
                    path
            );
    }

    public function is_family(        
    ): bool { 
        return $this->
            is_family; 
    }
    
    public function get_name(        
    ): string { 
        return $this->
            name; 
    }

    public function write(
        string $message, 
        $data, 
        SRW_Log_Level $level, 
        ?string $component_context = 
            null
    ) {
        $this->
            test_and_roll_over(
            );
        $is_signature_1 = 
            (
                $component_context === 
                    null
            );
        $active_component = 
            $is_signature_1 
                ? $this->
                    name 
                : $component_context;
        $msg_block = 
            $this->
                new_log_line(
                    $message, 
                    SRW_Log_Type::
                        Message, 
                    $level, 
                    $active_component
                );
        file_put_contents(
            $this->
                path, 
            $msg_block, 
            FILE_APPEND
        );

        if (
            $data !== 
                null
        ) {
            $json = 
                json_encode(
                    $data, 
                        JSON_PRETTY_PRINT | 
                            JSON_UNESCAPED_SLASHES
                );
            $data_block = 
                $this->
                    new_log_line(
                        $json, 
                        SRW_Log_Type::Data, 
                        $level, 
                        $active_component
                    );
            file_put_contents(
                $this->
                    path, 
                $data_block, 
                FILE_APPEND
            );
        }

        if (
            $is_signature_1
        ) {
            if (
                !$this->
                    is_family
            ) {
                $active_family_logs = 
                    SRW_Logger::family(
                    );
                if (
                    !empty(
                        $active_family_logs
                    )
                ) {
                    foreach (
                        $active_family_logs as 
                            $family_log
                    ) {
                        $family_log->
                            write(
                                $message, 
                                $data, 
                                $level, 
                                $this->
                                    name
                            );
                    }
                }
            }
            SRW_Logger::clear_calls(                
            );
        }
    }

    private function test_and_roll_over(
    ) {
        $roll_size = 
            SRW_Logger::
                get_roll_over_size(
                ); 
        if (
            file_exists(
                $this->
                    path
            ) && 
                filesize(
                    $this->
                        path
                ) >= 
                    $roll_size
        ) {
            $rotated = 
                str_replace(
                    '.log', 
                    '-' 
                        . date(
                            'Ymd-His'
                        ) 
                        . '.log', 
                    $this->
                        path
                );
            rename(
                $this->
                    path, 
                $rotated
            );
        }
    }

    private function new_log_line(
        string $entry, 
        SRW_Log_Type $type, 
        SRW_Log_Level $level, 
        string $component_context
    ): string {
        $prefix = 
            (
                $type === 
                    SRW_Log_Type::
                        Data
            ) 
                ? "\t" 
                : "";
        $max_msg_size = 
            SRW_Logger::
                get_max_message_size(                    
                );
        $chunks = 
            str_split(
                $entry, 
                $max_msg_size
            );
        $output = 
            "";
        $caller = 
            SRW_Logger::
                get_current_call(                    
                );
        $format_template = 
            SRW_Logger::
                get_format_string(                    
                );
        foreach (
            $chunks as 
                $index => 
                    $chunk
        ) {
            if (
                count(
                    $chunks
                ) > 
                    1 && 
                        $index < 
                            count(
                                $chunks
                            ) - 
                                1
            )
                $chunk = 
                    str_pad(
                        $chunk, 
                        $max_msg_size + 
                            3, 
                        "."
                    );
            $current_line = 
                $format_template;
            $site_name = 
                function_exists(
                    'get_bloginfo'
                ) 
                    ? get_bloginfo(
                        'name'
                    ) 
                    : 'WordPress';
            $basename = 
                (
                    $caller &&
                        isset(
                            $caller[
                                'basename'
                            ]
                        )
                )
                    ? $caller[
                        'basename'
                    ] 
                    : 'index.php';
            $line_num = 
                (
                    $caller &&
                        isset(
                            $caller[
                                'line'
                            ]
                        )
                )
                    ? $caller[
                        'line'
                    ] 
                    : 0;
            $func_meta = 
                (
                    $caller && 
                        !empty(
                            $caller[
                                'resolved_function'
                            ]
                        )
                ) 
                    ? " -> {$caller['resolved_function']}" 
                    : " -> {$component_context}()";
            $context_string = 
                "{$basename}:{$line_num}{$func_meta}";

            $current_line = 
                str_replace(
                    '{SITENAME}', 
                    $site_name, 
                    $current_line
                );
            $current_line = 
                str_replace(
                    '{LEVEL}', 
                    $level->
                        value, 
                    $current_line
                );
            $current_line = 
                str_replace(
                    '{CONTEXT}', 
                    $context_string, 
                    $current_line
                );
            $current_line = 
                str_replace(
                    '{MESSAGE}', 
                    "{$prefix}{$chunk}", 
                    $current_line
                );
            $current_line = 
                preg_replace_callback(
                    '/\{DATE:([^}]+)\}/', 
                    fn(
                        $m
                    ) => 
                        date(
                            $m[
                                1
                            ]
                        ), 
                    $current_line
                );
            $current_line = 
                preg_replace_callback(
                    '/\{TIME:([^}]+)\}/', 
                    function(
                        $m
                    ) {
                        $time_format = 
                            $m[
                                1
                            ];
                        if (
                            str_contains(
                                $time_format, 
                                'v'
                            )
                        ) {
                            $ms = 
                                sprintf(
                                    '%03d', 
                                    (int)(
                                        microtime(
                                            true
                                        ) * 
                                            1000
                                    ) % 
                                        1000
                                );
                            return date(
                                str_replace(
                                    'v', 
                                    $ms,
                                    $time_format
                                )
                            );
                        }
                        return date(
                            $time_format
                        );
                    }, 
                    $current_line
                );
            $output .= 
                $current_line 
                . PHP_EOL;
        }
        return $output;
    }
}