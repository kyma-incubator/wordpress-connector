<?php

namespace KymaProject\WordPressConnector;


require_once( dirname( __FILE__ ) . '/class-event.php' );
class EventSettings {

    private $name = "Wordpress";
    private $description = "Wordpress Event Definition";
    private $version = "1.0";
    private $option_group;
    private $page_name;
    public $events = array();

    function __construct($og, $page_name){
        $this->option_group = $og;
        $this->page_name = $page_name;
        $events_setting_name = $this->option_group.'_events';
        $events = get_option($events_setting_name);

        foreach ($events as $id => $event) {
            $this->events[$id] = Event::importArray($event, $events_setting_name, $id);
        }

        add_action("update_option_$events_setting_name", function ($old_value, $new_value) {
            // set a flag when the events change, so that the application registration gets updated
            if ($old_value !== $new_value) {
                update_option('kymaconnector_events_updated', '1');
            }
        }, 10, 2);
    }

    public static function subscribe_events(){
        $events = new EventSettings('kymaconnector', '');
        foreach($events->events as $id => $event){
            $event->register_hook();
        }
    }
    
    public static function install($option_group){
        $events = array();
        array_push($events, array('event_type'=>'user.created', 'event_version'=>'v1', 'hook'=>'user_register', 'description'=>'User Register Event v1', 'payload'=>'{"userId":{"type":"string","description":"Id of a User","title":"User uid"}}'));
        array_push($events, array('event_type'=>'comment.post', 'event_version'=>'v1', 'hook'=>'comment_post', 'description'=>'Comment Post Event v1', 'payload'=>'{"commentId":{"type":"string","description":"Unique id of a comment","title":"Comment id"},"commentStatus":{"type":"string","description":"Status of the comment","title":"Status of the comment"}'));

        add_option($option_group.'_events', $events);
    }

    public function settings_page() {

        // TODO: Set default values
        $events_setting_name = $this->option_group.'_events';
        register_setting($this->option_group, $this->option_group.'_event_api_name');
        register_setting($this->option_group, $this->option_group.'_event_api_description');
        register_setting($this->option_group, $this->option_group.'_event_api_version');
        register_setting($this->option_group, $events_setting_name);

        add_option($this->option_group.'_event_api_name', 'Wordpress Event`s');
        add_option($this->option_group.'_event_api_description', 'Wordpress Event`s');
        add_option($this->option_group.'_event_api_version', 'v1');

        add_settings_section( 
            $this->option_group.'_event_settings', 
            'Events Registration Settings', 
            array($this, 'settings_section_cb'), 
            $this->page_name
        );

        add_settings_field(
            $this->option_group.'_event_api_name',
            'Event API Name',
            array($this, 'field_api_name_cb'),
            $this->page_name,
            $this->option_group.'_event_settings'
        );
        
        $this->name = get_option($this->option_group.'_event_api_name');

        add_settings_field(
            $this->option_group.'_event_api_version',
            'Event API Version',
            array($this, 'field_api_version_cb'),
            $this->page_name,
            $this->option_group.'_event_settings'
        );
        
        $this->version = get_option($this->option_group.'_event_api_version');

        add_settings_field(
            $this->option_group.'_event_api_description',
            'Event API Description',
            array($this, 'field_api_description_cb'),
            $this->page_name,
            $this->option_group.'_event_settings'
        );
        
        $this->description = get_option($this->option_group.'_event_api_description');

        add_settings_field(
            $this->option_group.'_events',
            'Events',
            array($this, 'field_events_cb'),
            $this->page_name,
            $this->option_group.'_event_settings'
        );
    }

    public function settings_section_cb(){
        echo '<p>API Registration details.</p>';
    }

    public function field_api_name_cb(){
        $value = isset( $this->name ) ? $this->name : '';
        printf(
            '<input type="text" name="%s" value="%s">',
            esc_attr($this->option_group . '_event_api_name'),
            esc_attr($value)
        );
    }

    public function field_api_version_cb(){
        $value = isset( $this->version ) ? $this->version : '';
        printf(
            '<input type="text" name="%s" value="%s">',
            esc_attr($this->option_group . '_event_api_version'),
            esc_attr($value)
        );
    }

    public function field_api_description_cb(){
        $value = isset( $this->description ) ? $this->description : '';
        printf(
            '<textarea name="%s" rows="5" cols="50">%s</textarea>',
            esc_attr($this->option_group . '_event_api_description'),
            esc_textarea($value)
        );
    }

    public function field_events_cb(){
        ?>
        List of events forwarded to Kyma. The System will use <a href="https://codex.wordpress.org/Plugin_API/Action_Reference">Wordpress Action Hooks</a> to subscribe. <br/>The attributes deffined in the event payload section are mapped to the number of parameteres of the action hook.  
        <table id="event-settings">
            <tr>
                <th>Event Type</th>
                <th>Event Version</th>
                <th>Wordpress Action Hook</th>
                <th>Event Description</th>
                <th>Payload Definition</th>
                <th></th>
            </tr>
            <?php
            foreach ($this->events as $event) {
                echo $event->render_settings();
            }


            ?>
            </table><?php

            $events_setting_name = $this->option_group.'_events';

            $e = new Event();
            $jscode = "jQuery('#event-settings').append(".json_encode($e->render_settings()).");";
            $jscode .= "var events = jQuery(this).data('events');";
            $jscode .= "var prefix = '".$events_setting_name."[' + events + ']';";
            $jscode .= "jQuery('.no-prefix').attr('name', function(i, val){return prefix + val;}).removeClass('no-prefix');";
            $jscode .= "jQuery(this).data('events',  events + 1);";
            $jscode .= "return false;";

            $next_id = end($this->events)->get_id() + 1;
            echo '<a href="#" onclick="'.htmlspecialchars($jscode).'" data-events="'.$next_id.'">Add New Event</a>';

    }

    public function get_event_spec(){
        $topics = "";
        foreach ($this->events as $event) {
            $topics .= $event->get_event_spec().",";
        }
        $topics = rtrim($topics, ",");

        return sprintf(
            '{"spec":{"asyncapi":"1.0.0","info":{"title":"%s","version":"%s","description":"%s"},"topics":{%s}}}',
            $this->name,
            $this->version,
            $this->description,
            $topics
        );
    }
}
