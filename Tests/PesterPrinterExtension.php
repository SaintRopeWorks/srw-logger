<?php
declare(strict_types=1);

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\Event\TestSuite\StartedSubscriber;
use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\Test\PassedSubscriber;
use PHPUnit\Event\Test\Passed;

class PesterPrinterExtension implements Extension {
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void {
        
        // 1. Hook into Suite Starts to draw our master "Describe / Context" Group Headers
        $facade->registerSubscriber(new class implements StartedSubscriber {
            public function notify(Started $event): void {
                $name = $event->testSuite()->name();
                if ($name === '[Unit]' || $name === '[Integration]') {
                    echo PHP_EOL . "\033[1;36m" . $name . "\033[0m" . PHP_EOL;
                }
            }
        });

        // 2. Hook into Passing Tests to indent our "It" blocks cleanly underneath the parents
        $facade->registerSubscriber(new class implements PassedSubscriber {
            public function notify(Passed $event): void {
                $test = $event->test();
                if ($test instanceof \PHPUnit\Event\Code\TestMethod) {
                    $classParts = explode('\\', $test->className());
                    $className = end($classParts);
                    
                    $methodName = str_replace('_', ' ', $test->methodName());
                    
                    echo "  \033[32m✔\033[0m \033[90m{$className} ➜\033[0m {$methodName}" . PHP_EOL;
                }
            }
        });
    }
}
