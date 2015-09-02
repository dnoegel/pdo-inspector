<?php

namespace Dnoegel\DatabaseInspection\RouteProvider;

/**
 * Class ServerRouteProvider provides a request URI from the $_SERVER super global
 * Its a reasonable fallback for most web software
 *
 * @package Dnoegel\DatabaseInspection\RouteProvider
 */
class ServerRouteProvider implements RouteProvider
{
    public function getRoute()
    {
        return $_SERVER['REQUEST_URI'];
    }

}