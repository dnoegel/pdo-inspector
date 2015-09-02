<?php

namespace Dnoegel\DatabaseInspection\RouteProvider;

/**
 * Interface RouteProvider will provide a route for the current request. $_SERVER['REQUEST_URI'] might be sufficient in
 * many cases - but depending on the software, you might want to implement a custom route provider
 *
 * @package Dnoegel\DatabaseInspection\RouteProvider
 */
interface RouteProvider
{
    public function getRoute();
}