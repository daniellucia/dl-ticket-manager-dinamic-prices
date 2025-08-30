<?php

/**
 * Plugin Name: Dinamic prices for Ticket Manager
 * Description: Gestión de precios dinámicos para el gestor de tickets.
 * Version: 0.0.5
 * Author: Daniel Lúcia
 * Author URI: http://www.daniellucia.es
 * textdomain: dl-ticket-manager-dinamic-prices
 * Requires Plugins: dl-ticket-manager
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/src/Plugin.php';

add_action('plugins_loaded', function () {

    load_plugin_textdomain('dl-ticket-manager-dinamic-prices', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $plugin = new TMDinamicPricesPlugin();
    $plugin->init();
});
