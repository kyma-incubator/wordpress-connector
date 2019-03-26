<?php
/**
 * Plugin Name: Kyma Connector
 * Version:     0.1.0
 */

namespace KymaProject\WordPressConnector;

if (!defined('ABSPATH')) {
    exit;
}

require dirname(__FILE__) . '/class-core.php';
require dirname(__FILE__) . '/class-connector.php';
require dirname(__FILE__) . '/class-settings.php';

$core = new Core();
add_action('init', array($core, 'onInit'));

register_activation_hook(__FILE__, function () {
    Core::onActivation();
});
