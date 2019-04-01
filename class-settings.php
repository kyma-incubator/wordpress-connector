<?php

namespace KymaProject\WordPressConnector;

class Settings
{
    private $connector;
    private $event_settings;
    private $option_group;
    private $page_name;

    public function __construct($page_name, $option_group, Connector $connector, EventSettings $event_settings)
    {
        $this->connector = $connector;
        $this->event_settings = $event_settings;
        $this->option_group = $option_group;
        $this->page_name = $page_name;
    }

    public function addSettingsPage()
    {
        add_options_page(
            'Kyma Connector Settings',
            'Kyma Connector',
            'manage_options',
            $this->page_name,
            array($this, 'echoSettingsPage')
        );
    }

    public function enqueueScripts($hook)
    {
        if ($hook !== 'settings_page_' . $this->page_name) {
            return;
        }

        wp_enqueue_script('kyma-settings', Core::$scriptUrl . 'settings.js', array('jquery'));
        wp_localize_script('kyma-settings', 'kyma_ajax_vars', array(
            'connectnonce' => wp_create_nonce('kymaconnection'),
            'disconnectnonce' => wp_create_nonce('kymadisconnection')
        ));
    }

    public function registerSettings()
    {
        add_settings_section(
            'kymaconnector_settings',
            'Setup',
            null,
            $this->page_name
        );
        add_settings_field(
            'kymaconnector-setup',
            'Kyma Connection',
            array($this, 'echoFieldConnection'),
            $this->page_name,
            'kymaconnector_settings'
        );

        add_settings_section( 
            'kymaconnector_api_settings', 
            'API Registration Settings', 
            function () {
                echo '<p>API Registration details.</p>';
            }, 
            $this->page_name
        );

        add_settings_field(
            'kymaconnector_user',
            'Wordpress API User Name',
            array($this, 'field_user_cb'),
            $this->page_name,
            'kymaconnector_api_settings'
            );
        
        add_settings_field(
            'kymaconnector_password',
            'Wordpress API User Password',
            array($this, 'field_password_cb'),
            $this->page_name,
            'kymaconnector_api_settings'
            );

        add_settings_field(
            'kymaconnector_name',
            'Connector Name',
            array($this, 'field_name_cb'),
            $this->page_name,
            'kymaconnector_api_settings'
        );

        add_settings_field(
            'kymaconnector_description',
            'Connector Description',
            array($this, 'field_description_cb'),
            $this->page_name,
            'kymaconnector_api_settings'
        );

        register_setting($this->option_group, 'kymaconnector_user');
        register_setting($this->option_group, 'kymaconnector_password');
        register_setting($this->option_group, 'kymaconnector_name');
        register_setting($this->option_group, 'kymaconnector_description');

        $this->event_settings->settings_page();
    }

    public function echoFieldConnection()
    {
        $connected = $this->connector->isConnected();
        if ($connected === true) {
            echo '<p class="notice notice-success">Connection to Kyma works</p>';
            echo '<input type="button" id="kymadisconnectbtn" class="button" value="Disconnect">';
        } elseif (is_wp_error($connected)) {
            echo '<p class="notice notice-error">An error ocurred while checking the connection to Kyma: ' . esc_html($connected->get_error_message()) . '</p>';
        } else {
            echo '<input type="url" id="kyma-connect-url">';
            echo '<input type="button" id="kymaconnectbtn" class="button" value="Connect">';
        }
    }

    public function echoSettingsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to manage options for this site.'));
        }

        // In case settings were updated
        if (get_option('kymaconnector_events_updated', '0') === '1') {
            if (!empty(get_option('kymaconnector_metadata_url'))) {
                Connector::register_application($this->event_settings->get_event_spec());
                update_option('kymaconnector_events_updated', '0');
            }
        }
        
        // show error/update messages
        settings_errors( 'kymaconnector_messages' );

        ?>
        <div class="wrap">
        <h1>Kyma Connector Settings</h1>
        <div id="kymanotices"></div>
        <form method="post" action="options.php">
        <?php
        settings_fields($this->option_group);
        do_settings_sections($this->page_name);
        submit_button();
        ?>
        </form>
        </div>
        <?php
    }

    public function field_password_cb()
    {
        $setting = get_option('kymaconnector_password', '');
        printf('<input type="password" name="kymaconnector_password" value="%s">', esc_attr( $setting ));
    }

    public function field_user_cb()
    {   
        $setting = get_option('kymaconnector_user', '');
        printf('<input type="text" name="kymaconnector_user" value="%s">', esc_attr( $setting ));
    }

    public function field_name_cb()
    {   
        $setting = get_option('kymaconnector_name', '');
        printf('<input type="text" name="kymaconnector_name" value="%s">', esc_attr( $setting ));
    }

    public function field_description_cb()
    {   
        $setting = get_option('kymaconnector_description', '');
        printf('<textarea name="kymaconnector_description" rows="5" cols="50">%s</textarea>', esc_textarea( $setting ));
    }
}
