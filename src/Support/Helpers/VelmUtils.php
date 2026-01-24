<?php

namespace Velm\Core\Support\Helpers;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\error;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\warning;

enum ConsoleLogType: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ALERT = 'alert';
    case NOTE = 'note';
    case INTRO = 'intro';
    case OUTRO = 'outro';
    case ERROR = 'error';
}
class VelmUtils
{
    public function consoleLog(string $message, ConsoleLogType $type = ConsoleLogType::INFO): void
    {
        if (app()->runningInConsole()) {
            match ($type) {
                ConsoleLogType::INFO => \Laravel\Prompts\info($message),
                ConsoleLogType::WARNING => warning($message),
                ConsoleLogType::ALERT => alert($message),
                ConsoleLogType::NOTE => note($message),
                ConsoleLogType::INTRO => intro($message),
                ConsoleLogType::OUTRO => outro($message),
                ConsoleLogType::ERROR => error($message),
                default => note($message),
            };
        }
    }
}
