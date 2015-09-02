<?php

namespace Dnoegel\DatabaseInspection\Trace;

/**
 * Class DummyTrace can be used to disable tracing or for e.g. unit testing
 *
 * @package Dnoegel\DatabaseInspection\Trace
 */
class DummyTrace implements  Trace
{
    public function getTraceData()
    {
        return [null, null];
    }

}