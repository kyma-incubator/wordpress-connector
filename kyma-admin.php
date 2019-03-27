<?php

namespace KymaProject\WordPressConnector;

require_once( dirname( __FILE__ ) . '/class-openapi-generator.php' );

class PluginAdmin
{
    private $event_settings;

    public function init() {
        register_setting('kymaconnector', 'kymaconnector_user');
        register_setting('kymaconnector', 'kymaconnector_password');
        register_setting('kymaconnector', 'kymaconnector_name');
        register_setting('kymaconnector', 'kymaconnector_description');

        add_settings_section( 
            'kymaconnector_api_settings', 
            'API Registration Settings', 
            '\KymaProject\WordPressConnector\PluginAdmin::settings_section_cb', 
            'kymaconnector'
        );

        add_settings_field(
            'kymaconnector_user',
            'Wordpress API User Name',
            '\KymaProject\WordPressConnector\PluginAdmin::field_user_cb',
            'kymaconnector',
            'kymaconnector_api_settings'
            );
        
        add_settings_field(
            'kymaconnector_password',
            'Wordpress API User Password',
            '\KymaProject\WordPressConnector\PluginAdmin::field_password_cb',
            'kymaconnector',
            'kymaconnector_api_settings'
            );

        add_settings_field(
            'kymaconnector_name',
            'Connector Name',
            '\KymaProject\WordPressConnector\PluginAdmin::field_name_cb',
            'kymaconnector',
            'kymaconnector_api_settings'
        );

        add_settings_field(
            'kymaconnector_description',
            'Connector Description',
            '\KymaProject\WordPressConnector\PluginAdmin::field_description_cb',
            'kymaconnector',
            'kymaconnector_api_settings'
        );

        $this->event_settings = new EventSettings('kymaconnector');
        $this->event_settings->settings_page();
    }

    public static function options_page() {
        $admin = new PluginAdmin();
        $admin->init();

        add_menu_page(
            'Kyma Connector',
            'Kyma Connector',
            'manage_options',
            'kymaconnector',
            array($admin, 'options_page_html')
            );
    }

    public function options_page_html(){
        if (!current_user_can('manage_options')) {
            return;
        }

        // TODO: Add to cron and to change hook
        self::register_application($this->event_settings->get_event_spec());

        if ( isset( $_GET['settings-updated'] ) ) {
            // add settings saved message with the class of "updated"
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Settings Saved", 'updated' );
        }
            
        // show error/update messages
        settings_errors( 'kymaconnector_messages' );

        ?>
        <div class="wrap">
            <h1><?= esc_html(get_admin_page_title()); ?></h1>
            The Kyma Connector is registering Events and API's in Kyma and manages the connection.
            
            <form action="options.php" method="post">
                <?php
                settings_fields('kymaconnector');
                do_settings_sections('kymaconnector');
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public static function settings_section_cb(){
        echo '<p>API Registration details.</p>';
    }

    public static function field_password_cb()
    {
        $setting = get_option('kymaconnector_password');
        ?>
            <input type="password" name="kymaconnector_password" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    public static function field_user_cb()
    {   
        $setting = get_option('kymaconnector_user');
        ?>
            <input type="text" name="kymaconnector_user" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    public static function field_name_cb()
    {   
        $setting = get_option('kymaconnector_name');
        ?>
            <input type="text" name="kymaconnector_name" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
        <?php
    }

    public static function field_description_cb()
    {   
        $setting = get_option('kymaconnector_description');
        ?>
            <textarea name="kymaconnector_description" rows="5" cols="50"><?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?></textarea>
        <?php
    }

    // TODO: Run register only on updates
    public static function register_application($event_spec){

        $provider = "wordpress";

        $name = get_option('kymaconnector_name');;
        if( empty($name)){
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed - Please set application name.", 'error' );
            return;                
        }

        $description = get_option('kymaconnector_description');;
        if( empty($description)){
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed - Please set application description.", 'error' );
            return;                
        }


        $user = get_option('kymaconnector_user');
        $password = get_option('kymaconnector_password');
        if(empty($user) || empty($password)){
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed - Please set kyma user and password.", 'error' );
            return;
        }

        $api = new OpenAPIGenerator();
        $api_spec = $api->get_api_spec("Wordpress");

        $registration_body = '{"provider":"'.$provider.'","name":"'.$name.'","description":"'.$description.'","events":'.$event_spec.', "api":{"targetUrl":"'.get_rest_url().'","spec":'.$api_spec.', "credentials":{"basic":{"username":"'.$user.'", "password":"'.$password.'"}}}}';
        $url =  get_option("kymaconnector_metadata_url");
        $id = get_option("kymaconnector_application_id");
        //error_log($registration_body);
        
        $ch = curl_init();

        if (empty($id)){
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            curl_setopt($ch, CURLOPT_URL, $url . "/" . $id);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        
        $ch = PluginAdmin::add_clientcert_header($ch);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $registration_body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($registration_body))
        );
        
        $resp = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if($code == 200 ){
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registered", 'updated' );
            if (empty($id)){
                update_option('kymaconnector_application_id', json_decode($resp)->id);
            }
        } elseif($code == 404) {
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed due to 404 - ".$resp, 'error' );
            update_option('kymaconnector_application_id', '');
            PluginAdmin::register_application($event_spec);
        } else {

            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed - ".$resp, 'error' );
        }
    }
    
    private static function add_clientcert_header( $ch ) {
        $certDir = Connector::getKymaBasepath();
        $keyFile = $certDir . '/privkey.pem';
        $certFile = $certDir . '/certs/crt.pem';

        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        return $ch;
    }
}
