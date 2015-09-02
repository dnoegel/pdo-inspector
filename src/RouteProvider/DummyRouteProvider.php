<?php

namespace Dnoegel\DatabaseInspection\RouteProvider;

/**
 * Class DummyRouteProvider
 * @package Dnoegel\DatabaseInspection\RouteProvider
 */
class DummyRouteProvider implements RouteProvider
{
    public function getRoute()
    {
        return null;
    }

}