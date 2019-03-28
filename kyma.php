<?php
/**
 * Plugin Name: Kyma Connector
 * Plugin URI:  https://github.com/kyma-incubator/wordpress-connector
 * Description: Kyma Eventing and API Integration Plugin.
 * Version:     0.0.1
 * Author:      kyma-project.io
 * Author URI:  https://kyma-project.io/
 * License:     Apache-2.0
 */

namespace KymaProject\WordPressConnector;

if (!defined('ABSPATH')) {
    exit;
}

require dirname(__FILE__) . '/class-core.php';
require dirname(__FILE__) . '/class-connector.php';
require dirname(__FILE__) . '/class-settings.php';
require dirname(__FILE__) . '/kyma-admin.php';
require dirname(__FILE__) . '/event-settings.php';

// This has to run before everything else as it might instrument pretty early hooks.
add_action('plugins_loaded', '\KymaProject\WordPressConnector\EventSettings::subscribe_events');

$core = new Core(__FILE__);
add_action('init', array($core, 'onInit'));

register_activation_hook(__FILE__, function () {
    Core::onActivation();
});
