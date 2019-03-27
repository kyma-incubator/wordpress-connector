<?php

namespace KymaProject\WordPressConnector;

class Event {
    private $prefix;
    private $event_type = "";
    private $event_version = "";
    private $hook = "";
    private $description = "";
    private $payload = "";

    public static function importArray($a, $prefix, $id){
        $instance = new self();
        $instance->id = $id;
        $instance->prefix = $prefix."[".$id."]";
        $instance->event_type = $a['event_type'];
        $instance->event_version = $a['event_version'];
        $instance->hook = $a['hook'];
        $instance->description = $a['description'];
        $instance->payload = $a['payload'];

        return $instance;
    }

    public function get_id(){
        return $this->id;
    }

    public function render_settings(){
        // TODO: Add Javascript to add and remove settings.
        $class = isset( $this->prefix ) ? '' : 'no-prefix';
        $s = '<tr>';
        $event_type = isset( $this->event_type ) ? esc_attr( $this->event_type ) : '';
        $s .= '<td style="vertical-align: top;"><input type="text" class="'.$class.'" name="'.$this->prefix.'[event_type]" value="'.$event_type.'"></td>';

        $event_version = isset( $this->event_version ) ? esc_attr( $this->event_version ) : '';
        $s .= '<td style="vertical-align: top;"><input type="text" class="'.$class.'" name="'.$this->prefix.'[event_version]" value="'.$event_version.'"></td>';

        $hook = isset( $this->hook ) ? esc_attr( $this->hook ) : '';
        $s .= '<td style="vertical-align: top;"><input type="text" class="'.$class.'" name="'.$this->prefix.'[hook]" value="'.$hook.'"></td>';

        $description = isset( $this->description ) ? esc_attr( $this->description) : '';
        $s .= '<td style="vertical-align: top;"><textarea class="'.$class.'" name="'.$this->prefix.'[description]" rows="5" cols="30">'.$description.'</textarea></td>';


        // TODO: Validate json
        $payload = isset( $this->payload ) ? esc_attr( $this->payload) : '';
        $s .=  '<td style="vertical-align: top;"><textarea class="'.$class.'" name="'.$this->prefix.'[payload]" rows="5" cols="30">'.$payload.'</textarea></td>';
        $s .=  '<td><a href="#" onclick="jQuery(this).closest(\'tr\').remove();return false;">Remove</a></td>';

        $s .= '</tr>';
        return $s;
    }

    public function get_event_spec(){
        return '"'.$this->event_type.'.'.$this->event_version.'":{"subscribe":{"summary":"'.$this->description.'","payload":'.$this->get_payload_spec().'}}';
    }

    private function get_payload_spec(){
        $required = '';

        $payload = json_decode($this->payload, true);
        foreach ($payload as $key => $value){
            $required .= '"'.$key.'",';
        }

        $required = rtrim($required, ",");

        return '{"type":"object","required":['.$required.'],"properties":'.$this->payload.'}';
    }

    public function hook_callback(){
        $args = func_get_args();

        $payload = json_decode($this->payload, true);
        $i=0;

        $data = "";

        foreach ($payload as $key => $value) {
            $data .= '"'.$key.'":"'.$args[$i].'",';
            $i++;
        }

        $data = trim($data, ",");

        $this->send_event($data);
    }

    public function register_hook(){
        $attr = count(json_decode($this->payload, true));
        add_action( $this->hook, array($this, 'hook_callback'), 10, $attr);
    }

    // Send Event
    public function send_event($data ){

        $data = '{"event-type": "'.$this->event_type.'", "event-type-version": "'.$this->event_version.'", "event-id": "'. $this->gen_uuid() .'","event-time": "'.date("c",time()).'","data": {'.$data.'}}';
        error_log($data);

        $url =  get_option("kymaconnector_event_url");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        $ch = PluginAdmin::add_clientcert_header($ch);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );

        // TODO: Add error handling
        $resp = curl_exec($ch);

        error_log($resp);


    }

    public static function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    // TODO: 3. Test integration
}
