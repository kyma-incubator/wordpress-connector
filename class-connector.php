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
        mkdir($dir, 0777, true);

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
        $this->storeCertificate($csrPostResponseBodyJson->crt, 'crt.pem');
        $this->storeCertificate($csrPostResponseBodyJson->clientCrt, 'clientCrt.pem');
        $this->storeCertificate($csrPostResponseBodyJson->caCrt, 'caCrt.pem');
        // TODO check if they were successfully stored

        // Store important URLs
        update_option('kymaconnector_metadata_url', $body_json->api->metadataUrl);
        //update_option('', $body_json->api->infoUrl);
        //update_option('', $body_json->api->certificatesUrl);
        update_option('kymaconnector_event_url', $body_json->api->eventsUrl);
        
        return true;
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
        mkdir($folder, 0777, true);
        $path = "$folder/$fileName";

        // TODO ensure existance of directory
        // TODO check writing rights
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
}
