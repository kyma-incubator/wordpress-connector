<?php

namespace KymaProject\WordPressConnector;

class Core
{
    private $connector;

    public static function onActivation()
    {
        // some initial stuff after installation
    }

    public function onInit()
    {
        $this->connector = new Connector();

        $settings = new Settings();
        add_action('admin_menu', array($settings, 'addSettingsPage'));
        add_action('admin_init', array($settings, 'registerSettings'));

        add_action('wp_ajax_connect_to_kyma', array($this, 'onAjaxKymaConnect'));
    }

    public function onAjaxKymaConnect()
    {
        // TODO check access rights of the user
        
        check_ajax_referer( 'kymaconnection' );
        $url = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);

        $result = $this->connector->connect($url);
        if (is_wp_error($result)) {
            wp_send_json_error($result);
            return;
        }

        error_log($result);
        wp_send_json('hello');
    }


}
