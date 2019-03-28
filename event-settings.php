<?php

namespace KymaProject\WordPressConnector;


require_once( dirname( __FILE__ ) . '/class-event.php' );
class EventSettings {

    private $name = "Wordpress";
    private $description = "Wordpress Event Definition";
    private $version = "1.0";
    private $option_group;
    public $events = array();

    function __construct($og){
        $this->option_group = $og;
        $events_setting_name = $this->option_group.'_events';
        $events = get_option($this->option_group.'_events');

        foreach ($events as $id => $event) {
            $this->events[$id] = Event::importArray($event, $events_setting_name, $id);
        }
    }

    public static function subscribe_events(){
        $events = new EventSettings('kymaconnector');
        foreach($events->events as $id => $event){
            $event->register_hook();
        }
    }
    
    public static function install($option_group){
        $events = array();
        array_push($events, array('event_type'=>'user.created', 'event_version'=>'v1', 'hook'=>'user_register', 'description'=>'User Register Event v1', 'payload'=>'{"userId":{"type":"string","description":"Id of a User","title":"User uid"}}'));
        array_push($events, array('event_type'=>'comment.post', 'event_version'=>'v1', 'hook'=>'comment_post', 'description'=>'Comment Post Event v1', 'payload'=>'{"commentId":{"type":"string","description":"Unique id of a comment","title":"Comment id"},"commentAuthorEmail":{"type":"string","description":"Email of an author","title":"Authors email"},"commentContent":{"type":"string","description":"Content of a comment","title":"Comment content"}}'));

        add_option($option_group.'_events', $events);
    }

    public function settings_page() {

        // TODO: Set default values
        $events_setting_name = $this->option_group.'_events';
        register_setting($this->option_group, $this->option_group.'_event_api_name');
        register_setting($this->option_group, $this->option_group.'_event_api_description');
        register_setting($this->option_group, $this->option_group.'_event_api_version');
        register_setting($this->option_group, $events_setting_name);


        add_settings_section( 
            $this->option_group.'_event_settings', 
            'Events Registration Settings', 
            array($this, 'settings_section_cb'), 
            $this->option_group
        );

        add_settings_field(
            $this->option_group.'_event_api_name',
            'Event API Name',
            array($this, 'field_api_name_cb'),
            $this->option_group,
            $this->option_group.'_event_settings'
        );
        
        $this->name = get_option($this->option_group.'_event_api_name');

        add_settings_field(
            $this->option_group.'_event_api_version',
            'Event API Version',
            array($this, 'field_api_version_cb'),
            $this->option_group,
            $this->option_group.'_event_settings'
        );
        
        $this->version = get_option($this->option_group.'_event_api_version');

        add_settings_field(
            $this->option_group.'_event_api_description',
            'Event API Description',
            array($this, 'field_api_description_cb'),
            $this->option_group,
            $this->option_group.'_event_settings'
        );
        
        $this->description = get_option($this->option_group.'_event_api_description');

        add_settings_field(
            $this->option_group.'_events',
            'Events',
            array($this, 'field_events_cb'),
            $this->option_group,
            $this->option_group.'_event_settings'
        );
    }

    public function settings_section_cb(){
        echo '<p>API Registration details.</p>';
    }

    public function field_api_name_cb(){
        $value = isset( $this->name ) ? esc_attr( $this->name ) : '';
        echo '<input type="text" name="'.$this->option_group.'_event_api_name" value="'.$value.'">';
    }

    public function field_api_version_cb(){
        $value = isset( $this->version ) ? esc_attr( $this->version ) : '';
        echo '<input type="text" name="'.$this->option_group.'_event_api_version" value="'.$value.'">';
    }

    public function field_api_description_cb(){
        $value = isset( $this->description ) ? esc_attr( $this->description ) : '';
        echo '<textarea name="'.$this->option_group.'_event_api_description" rows="5" cols="50">'.$value.'</textarea>';
    }

    public function field_events_cb(){
        ?>
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
        $spec = '{"spec":{"asyncapi":"1.0.0","info":{"title":"'.$this->name.'","version":"'.$this->version.'","description":"'.$this->description.'"},"topics":{'.$topics.'}}}';
        return $spec;
    }
}
