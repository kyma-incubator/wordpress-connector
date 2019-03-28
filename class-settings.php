<?php

namespace KymaProject\WordPressConnector;

class Settings
{
    const PAGESLUG = 'kymaconnector-settings';

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
        wp_localize_script('kyma-settings', 'kyma_ajax_vars', array('nonce' => wp_create_nonce('kymaconnection')));
    }

    public function registerSettings()
    {
        add_settings_section(
            'kymaconnector_settings',
            'Setup',
            null,
            'kymaconnector-settings'
        );
        add_settings_field(
            'kymaconnector-setup',
            'Kyma Connection',
            array($this, 'echoFieldConnection'),
            'kymaconnector-settings',
            'kymaconnector_settings'
        );
    }

    public function echoFieldConnection()
    {
        $connected = $this->connector->isConnected();
        if ($connected === true) {
            echo '<p class="notice notice-success">Connection to Kyma works</p>';
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

        ?>
        <div class="wrap">
        <h1>Kyma Connector Settings</h1>
        <div id="kymanotices"></div>
        <form method="post" action="options.php">
        <?php
        settings_fields('kymaconnector_settings');
        do_settings_sections('kymaconnector-settings');
        submit_button();
        ?>
        </form>
        </div>
        <?php
    }
}
