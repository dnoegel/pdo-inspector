<?php

namespace Dnoegel\DatabaseInspection\RouteProvider;

/**
 * Class ShopwareRouteProvider provides routes for Shopware. It is aware of subrequest and certain ajax request
 *
 * @package Dnoegel\DatabaseInspection\RouteProvider
 */
class ShopwareRouteProvider implements RouteProvider
{
    public function getRoute()
    {
        if (!Shopware()->Container()->initialized('front')) {
            return $this->getFallbackRoute();
        }

        try {
            return Shopware()->Front()->Request()->getRequestUri();
        } catch (\Exception $e) {
        }

        return $this->getFallbackRoute();
    }

    private function getFallbackRoute()
    {

        if (isset($_SERVER['REQUEST_URI'])) {
            return $_SERVER['REQUEST_URI'];
        }

        return '';
    }

}