<?php

namespace Dnoegel\DatabaseInspection\Trace;

/**
 * Class DebugTrace is the debug_backtrace() implementation for `Trace`. It might be slow, but provides a lot of additional
 * info as e.g. the actual code causing the problem in question.
 *
 * @package Dnoegel\DatabaseInspection\Trace
 */
class DebugTrace implements Trace
{

    private $blacklistedClasses = '#^(Dnoegel\\\DatabaseInspection.*|Doctrine\\\DBAL.*|Doctrine\\\ORM.*|Zend_Db.*|Enlight_Components_Db.*)#i';

    /**
     * @Inheritdoc
     */
    public function getTraceData()
    {
        $trace = debug_backtrace();
        $traceSimple = $this->simplifyTrace($trace);

        $code = $this->getRelevantCodeFromTrace($trace);

        return [$code, (object)array_values($traceSimple)];
    }

    /**
     * Simplifies the trace to be human readable
     *
     * @param $trace
     * @return array
     */
    private function simplifyTrace($trace)
    {
        $traceSimple = array_map(
        // format to simplified syntax
            function ($trace) {
                $line = isset($trace['line']) ? $trace['line'] : null;
                return $trace['class'] . ': ' . $trace['function'] . ':  ' . $line;
            },
            // filter blacklisted classes
            array_filter(
                $trace,
                function ($traceEntry) {
                    if (!isset($traceEntry['class'])) {
                        return false;
                    }
                    return !preg_match($this->blacklistedClasses, $traceEntry['class']);
                }
            )
        );

        return $traceSimple;
    }

    /**
     * Will figure out the latest caller before the program flow went into the DB layer. For that latest call
     * the relevant code of the surrounding method will be returned.
     *
     * @param $trace
     * @return array|null
     */
    private function getRelevantCodeFromTrace($trace)
    {
        // Iterate traces, ignore blacklisted adapters and figure out the latest, relevant caller.
        // Will also store the line number from the following caller, as this is the line number in the relevant caller
        $relevantCaller = null;
        $linNumber = null;
        foreach ($trace as $relevantCaller) {
            if (!isset($relevantCaller['class']) || !preg_match($this->blacklistedClasses, $relevantCaller['class'])) {
                break;
            }
            $linNumber = isset($relevantCaller['line']) ? $relevantCaller['line']: null;
        }

        $code = null;
        if (!$relevantCaller || !isset($relevantCaller['class'])) {
            return null;
        }

        // Get the relevant code from the relevant caller
        $class = new \ReflectionClass($relevantCaller['class']);
        $method = $class->getMethod($relevantCaller['function']);
        $start = $method->getStartLine();
        $end = $method->getEndLine();
        $methodCode = array_slice(file($class->getFileName()), $start - 1, $end - $start + 1);

        // build line numbers as keys. The relevant line number will be replaced with "!!!"
        $keys = range($start, $end);
        if ($linNumber) {
            $idx = array_search($linNumber, $keys);
            $keys[$idx] = '!!!';
        }
        $code = array_combine($keys, $methodCode);

        // remove trailing new lines
        $code = array_map(function ($line) {
            return trim($line, "\n");
        }, $code);

        return $code;
    }


} 