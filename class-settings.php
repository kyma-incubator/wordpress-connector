<?php

namespace KymaProject\WordPressConnector;

class Settings
{
    const PAGESLUG = 'kymaconnector-settings';

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
        // TODO: put JavaScript into separate file
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#kymaconnectbtn").click(function() {
                    // TODO: show a spinner
                    // TODO: disable button
                    var this2 = this;
                    let url = document.getElementById('kyma-connect-url').value;
                    $.post(ajaxurl, {
                        _ajax_nonce: '<?php echo wp_create_nonce('kymaconnection') ?>',
                        action: "connect_to_kyma",
                        url: url
                    }, function(data) {
                        // TODO: hide spinner
                        console.log(data);
                        if (data.success === false) {
                            displayNotice('notice-error', data.data[0].message || ("Unknown error, code " + data.data[0].code));
                            return;
                        }

                        displayNotice('notice-success', 'Successfully connected to Kyma');
                    });
                });
            });

            function displayNotice(className, message) {
                var notice = document.createElement('div');
                notice.classList = 'notice ' + className;
                notice.innerHTML = '<p>' + message + '</p>';
                document.getElementById('kymanotices').appendChild(notice);
            }
        </script>
        <input type="url" id="kyma-connect-url">
        <input type="button" id="kymaconnectbtn" class="button" value="Connect">
        <?php
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
