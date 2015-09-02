<?php

namespace Dnoegel\DatabaseInspection\Trace;

/**
 * Interface Trace is the generic interface for getting trace data for the current request
 *
 * @package Dnoegel\DatabaseInspection\Trace
 */
interface Trace
{

    /**
     * @return [$relevantCode, $trace]
     */
    public function getTraceData();
}