<?php

namespace KymaProject\WordPressConnector;

use WP_Error;
use WP_Http;

class Connector
{
    private $http;

    public function __construct()
    {
        $this->http = new WP_Http();
    }

    public function connect($url)
    {
        // Retrieve the CSR info from the given URL
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return $response;
        }

        if ($response['response']['code'] !== 200) {
            return new WP_Error($response['response']['code'], 'Could not retrieve CSR info');
        }

        $body = $response['body'];
        $body_json = json_decode($body);

        // Generate and store a private key
        // TODO: use the key algorithm specified in $response
        $key = openssl_pkey_new(array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
        if ($key === false) {
            return new WP_Error(500, 'Could not generate private key');
        }

        $dir = $this->getKymaBasepath();
        try {
            $this->ensureDirectory($dir);
        } catch (Exception $exception) {
            return new WP_Error(500, $exception->getMessage());
        }

        if (openssl_pkey_export_to_file($key, $dir . '/privkey.pem') === false) {
            return new WP_Error(500, 'Could not store private key');
        }
        
        // Generate a CSR
        // TODO: possibly respect the certificate extensions specified in $response (none at the time of writing)
        $dn = $this->getDistinguishedName($body_json->certificate->subject);
        $csr = openssl_csr_new($dn, $key);
        if ($csr === false) {
            return new WP_Error(500, 'Could not generate CSR');
        }
        if (openssl_csr_export($csr, $csrBase64) === false) {
            return new WP_Error(500, 'Could not export CSR');
        }

        // Send the CSR
        $csrURL = $body_json->csrUrl;
        $csrPostBody = array('csr' => base64_encode($csrBase64));
        $csrPostResponse = $this->http->post($csrURL, array('headers' => ['Content-Type' => 'application/json'], 'body' => json_encode($csrPostBody)));
        if (is_wp_error($csrPostResponse)) {
            return new WP_Error(500, 'Could not send CSR');
        }

        if ($csrPostResponse['response']['code'] !== 201) {
            return new WP_Error($csrPostResponse['response']['code'], 'CSR request was not successful');
        }

        $csrPostResponseBody = $csrPostResponse['body'];
        $csrPostResponseBodyJson = json_decode($csrPostResponseBody);
        if (
            empty($csrPostResponseBodyJson) ||
            !property_exists($csrPostResponseBodyJson, 'crt') ||
            !property_exists($csrPostResponseBodyJson, 'clientCrt') ||
            !property_exists($csrPostResponseBodyJson, 'caCrt')
        ) {
            return new WP_Error(500, 'The CSR response did not meet the expected format');
        }

        // Store certificates
        try {
            $this->storeCertificate($csrPostResponseBodyJson->crt, 'crt.pem');
            $this->storeCertificate($csrPostResponseBodyJson->clientCrt, 'clientCrt.pem');
            $this->storeCertificate($csrPostResponseBodyJson->caCrt, 'caCrt.pem');
        } catch (Exception $exception) {
            return new WP_Error(500, 'Could not store one or more certificates: ' + $exception->getMessage());
        }

        // Store important URLs
        update_option('kymaconnector_metadata_url', $body_json->api->metadataUrl);
        //update_option('', $body_json->api->infoUrl);
        //update_option('', $body_json->api->certificatesUrl);
        update_option('kymaconnector_event_url', $body_json->api->eventsUrl);
        
        return true;
    }

    public function disconnect()
    {
        $result = $this->deregister_application();
        if (is_wp_error($result)) {
            return $result;
        }

        update_option('kymaconnector_metadata_url', '');
        update_option('kymaconnector_event_url', '');
        update_option('kymaconnector_application_id', '');

        // TODO: delete certificates?
    }

    /**
     * @param string $string description
     * @return string[]
     */
    private function getDistinguishedName($string)
    {
        $dn = array();
        $parts = explode(',', $string);
        foreach ($parts as $value) {
            $pair = explode('=', $value);
            $dn[$pair[0]] = $pair[1];
        }
        return $dn;
    }

    private function storeCertificate($data, $fileName)
    {
        $folder = $this->getKymaBasepath() . "/certs"; 
        $this->ensureDirectory($folder);
        $path = "$folder/$fileName";

        // TODO sanitize file names

        $handle = fopen($path, 'w');
        fwrite($handle, base64_decode($data));
        fclose($handle);
    }

    public static function getKymaBasepath()
    {
        $uploadDir = wp_upload_dir();
        return $uploadDir['basedir'] . '/kyma';
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        $url = get_option("kymaconnector_metadata_url");
        $applicationId = get_option("kymaconnector_application_id");

        if (empty($url) || empty($applicationId)) {
            return false;
        }
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . "/" . $applicationId);
        
        $ch = self::add_clientcert_header($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if ($code !== 200) {
            return new WP_Error($code, 'The metadata URL could not be accessed');
        }

        return true;
    }

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
        
        $ch = self::add_clientcert_header($ch);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $registration_body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($registration_body))
        );
        
        $resp = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        // TODO: Find alternative to add_settings_error() as they will not be shown on the Plugin screen when (de)activating a plugin
        if($code == 200 ){
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registered", 'updated' );
            if (empty($id)){
                update_option('kymaconnector_application_id', json_decode($resp)->id);
            }
        } elseif($code == 404) {
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed due to 404 - ".$resp, 'error' );
            update_option('kymaconnector_application_id', '');
            self::register_application($event_spec);
        } else {
            add_settings_error( 'kymaconnector_messages', 'kymaconnector_message', "Application Registration Failed - ".$resp, 'error' );
            return new WP_Error($code, $resp);
        }

        return true;
    }

    public function deregister_application()
    {
        $url =  get_option("kymaconnector_metadata_url");
        $id = get_option("kymaconnector_application_id");
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . "/" . $id);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        
        $ch = self::add_clientcert_header($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $resp = curl_exec($ch);

        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        if($code !== 204){
            return new WP_Error($code);
        }

        return true;
    }
    
    public static function add_clientcert_header( $ch ) {
        $certDir = Connector::getKymaBasepath();
        $keyFile = $certDir . '/privkey.pem';
        $certFile = $certDir . '/certs/crt.pem';

        curl_setopt($ch, CURLOPT_SSLKEY, $keyFile);
        curl_setopt($ch, CURLOPT_SSLCERT, $certFile);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        
        return $ch;
    }

    private function ensureDirectory($path)
    {
        if (file_exists($path) === false) {
            $success = mkdir($path, 0640, true);
            if ($success === false) {
                throw new Exception('Could not create folder ' + $folder);
            }
        }

        if (is_dir($path) === false) {
            throw new Exception('Could not find folder ' + $folder);
        }

        if (is_writable($path) === false) {
            throw new Exception("Folder $folder is not writable");
        }
    }
}
