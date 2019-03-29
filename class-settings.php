<?php

namespace KymaProject\WordPressConnector;

class Settings
{
    const PAGESLUG = 'kymaconnector-settings';
    const OPTIONGROUP = 'kymaconnector';

    private $connector;

    public function __construct(Connector $connector)
    {
        $this->connector = $connector;
    }

    public function addSettingsPage()
    {
        add_options_page(
            'Kyma Connector Settings',
            'Kyma Connector',
            'manage_options',
            self::PAGESLUG,
            array($this, 'echoSettingsPage')
        );
    }

    public function enqueueScripts($hook)
    {
        if ($hook !== 'settings_page_' . self::PAGESLUG) {
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
            self::PAGESLUG
        );
        add_settings_field(
            'kymaconnector-setup',
            'Kyma Connection',
            array($this, 'echoFieldConnection'),
            self::PAGESLUG,
            'kymaconnector_settings'
        );

        add_settings_section( 
            'kymaconnector_api_settings', 
            'API Registration Settings', 
            function () {
                echo '<p>API Registration details.</p>';
            }, 
            self::PAGESLUG
        );

        add_settings_field(
            'kymaconnector_user',
            'Wordpress API User Name',
            array($this, 'field_user_cb'),
            self::PAGESLUG,
            'kymaconnector_api_settings'
            );
        
        add_settings_field(
            'kymaconnector_password',
            'Wordpress API User Password',
            array($this, 'field_password_cb'),
            self::PAGESLUG,
            'kymaconnector_api_settings'
            );

        add_settings_field(
            'kymaconnector_name',
            'Connector Name',
            array($this, 'field_name_cb'),
            self::PAGESLUG,
            'kymaconnector_api_settings'
        );

        add_settings_field(
            'kymaconnector_description',
            'Connector Description',
            array($this, 'field_description_cb'),
            self::PAGESLUG,
            'kymaconnector_api_settings'
        );

        register_setting(self::OPTIONGROUP, 'kymaconnector_user');
        register_setting(self::OPTIONGROUP, 'kymaconnector_password');
        register_setting(self::OPTIONGROUP, 'kymaconnector_name');
        register_setting(self::OPTIONGROUP, 'kymaconnector_description');

        $this->event_settings = new EventSettings(self::OPTIONGROUP, self::PAGESLUG);
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

        // TODO: Add to cron and to change hook
        if (!empty(get_option('kymaconnector_metadata_url'))) {
            Connector::register_application($this->event_settings->get_event_spec());
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Settings Saved", 'updated' );
        }
            
        // show error/update messages
        settings_errors( 'kymaconnector_messages' );

        ?>
        <div class="wrap">
        <h1>Kyma Connector Settings</h1>
        <div id="kymanotices"></div>
        <form method="post" action="options.php">
        <?php
        settings_fields(self::OPTIONGROUP);
        do_settings_sections(self::PAGESLUG);
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
