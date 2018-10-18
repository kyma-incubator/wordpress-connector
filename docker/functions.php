function comment_post_function( $comment_ID, $comment_approved, $commentdata ) {
    if( 1 === $comment_approved ){
  $url = 'http://ec-default-event-service-external-api:8081/ec-default/v1/events';

  wp_remote_post($url, array(
    'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
    'body'      => '{
            "event-type": "comment.post",
            "event-type-version": "v1",
            "event-id": "'.gen_uuid().'",
            "event-time": "'.date("c",time()).'",
            "data": {
                "commentId": "'.$comment_ID.'",
                "commentAuthorEmail": "'.$commentdata['comment_author_email'].'",
                "commentContent": "'.$commentdata['comment_content'].'"
              },
            "_nodeFactory": {
              "_cfgBigDecimalExact": false
          }}',
    'method'    => 'POST'
));
    }
}
add_action( 'comment_post', 'comment_post_function', 10, 3);


function user_registration_handler( $user_id ) {
    $url = 'http://ec-default-event-service-external-api:8081/ec-default/v1/events';

  wp_remote_post($url, array(
    'headers'   => array('Content-Type' => 'application/json; charset=utf-8'),
    'body'      => '{
            "event-type": "customer.created",
            "event-type-version": "v1",
            "event-id": "'.gen_uuid().'",
            "event-time": "'.date("c",time()).'",
            "data": {
                "userId": "'.$user_id.'"
              }
            "_nodeFactory": {
              "_cfgBigDecimalExact": false
          }}',
    'method'    => 'POST'
));
}

add_action( 'user_register', 'user_registration_handler' );

function gen_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}


class kyma_events_options_page {

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_menu() {
        add_options_page(
            'Kyma Events Configuration',
            'Kyma Events Configuration',
            'manage_options',
            'kyma_events_registration',
            array(
                $this,
                'kyma_events_page'
            )
        );
    }

function  kyma_events_page() {
        if (($registered = $_GET['registered']) != null) {
            $message = '';

            if($registered == 'true'){
                $message = 'Wordpress events was registered in Kyma';
                $noticeColour = 'notice-success';
            }else{
                $message = 'Wordpress events could not be registered in Kyma';
                $noticeColour = 'notice-error';
            }

            echo '<div class="notice '.$noticeColour.' is-dismissible">
                        <p>'.$message.'</p>
                </div>';
        }

        echo '<div id="content">
                <p>
                  <h1>Kyma Events Configuration</h1>
                  <h3>
                    Kyma is a cloud-native application development framework.It provides the last mile capabilities that a developer needs to build a cloud-native application using several open-source projects such as Kubernetes, Istio, NATS, Kubeless, and Prometheus, to name a few. It is designed natively on Kubernetes and, therefore, it is portable to all major cloud providers.
                  </h3>
                  <h4>Use form below to connect Your WordPress application to Kyma.</h4>
                </p>

                <form action="'.esc_url(admin_url("admin-post.php")).'" method="post">
        	         <label for="provider">Provider name</label>
                   </br>
                   <input name="provider" id="provider" value="WordPress" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="name">API name</label>
                   </br>
                   <input name="name" id="name" value="wordpress-events" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="description">Description</label>
                   </br>
                   <input name="description" id="description" value="Wordpress Events" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_asyncapi">Specification asyncapi</label>
                   </br>
                   <input name="spec_asyncapi" id="spec_asyncapi" value="1.0.0" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_title">Specification title</label>
                   </br>
                   <input name="spec_title" id="spec_title" value="wp-events" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_version">Specification version</label>
                   </br>
                   <input name="spec_version" id="spec_version" value="v1" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_description">Specification description</label>
                   </br>
                   <input name="spec_description" id="spec_description" value="WP Events v1" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="events">Register events (with user created and comment post event as example)</label>
                   </br>
                   <textarea style="margin-bottom:15px;margin-top:5px" rows="8" cols="50" name="events" id="events">"user.created.v1":{"subscribe":{"summary":"User Register Event v1","payload":{"type":"object","required":["userId"],"properties":{"userId":{"type":"string","description":"Id of a User","title":"User uid"}}}}},"comment.post.v1":{"subscribe":{"summary":"Comment Post Event v1","payload":{"type":"object","required":["commentId"],"properties":{"commentId":{"type":"string","description":"Unique id of a comment","title":"Comment id"},"commentAuthorEmail":{"type":"string","description":"Email of an author","title":"Authors email"},"commentContent":{"type":"string","description":"Content of a comment","title":"Comment content"}}}}}</textarea>
                   <input type="hidden" name="action" value="events_registration_form">
                   <br/>
                   <input class="button-primary" type="submit" value="Register API">
                 </form>
            </div>';
            }
        }

new kyma_events_options_page;


function events_registration_call() {
    $url = 'http://metadata-service-external-api:8081/ec-default/v1/metadata/services';
    $registration_body = build_events_registartion_body($_POST);

    $response = wp_remote_post(
        $url,
        array( 'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => $registration_body, 'method' => 'POST' ));

    $redirectUrl = 'options-general.php?page=kyma_events_registration&registered=';

    if(is_wp_error($response)){
        $redirectUrl = $redirectUrl.'false';
    }else{
        $redirectUrl = $redirectUrl.'true';
    }

    wp_redirect(admin_url($redirectUrl));
}

function  build_events_registartion_body($registration_form){
   $registration_body = '{"provider":"'.$registration_form['provider'].'","name":"'.$registration_form['name'].'","description":"'.$registration_form['description'].'","events":{"spec":{"asyncapi":"'.$registration_form['spec_asyncapi'].'","info":{"title":"'.$registration_form['spec_title'].'","version":"'.$registration_form['spec_version'].'","description":"'.$registration_form['spec_description'].'"},"topics":{'.stripslashes($registration_form['events']).'}}}}';
   return $registration_body;
}

add_action( 'admin_post_events_registration_form', 'events_registration_call' );

add_action( 'init', function() {
    global $wp_rewrite;
    $wp_rewrite->set_permalink_structure( '/%postname%/' );
    run_activate_plugin( 'basic-auth.php' );
} );


function run_activate_plugin( $plugin ) {
    $current = get_option( 'active_plugins' );
    $plugin = plugin_basename( trim( $plugin ) );

    if ( !in_array( $plugin, $current ) ) {
        $current[] = $plugin;
        sort( $current );
        do_action( 'activate_plugin', trim( $plugin ) );
        update_option( 'active_plugins', $current );
        do_action( 'activate_' . trim( $plugin ) );
        do_action( 'activated_plugin', trim( $plugin) );
    }

    return null;
}

class kyma_api_options_page {

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_menu() {
        add_options_page(
            'Kyma API Configuration',
            'Kyma API Configuration',
            'manage_options',
            'kyma_api_registration',
            array(
                $this,
                'kyma_api_page'
            )
        );
    }

function  kyma_api_page() {
        if (($registered = $_GET['registered']) != null) {
            $message = '';

            if($registered == 'true'){
                $message = 'Wordpress API was registered in Kyma';
                $noticeColour = 'notice-success';
            }else{
                $message = 'Wordpress API could not be registered in Kyma';
                $noticeColour = 'notice-error';
            }

            echo '<div class="notice '.$noticeColour.' is-dismissible">
                        <p>'.$message.'</p>
                </div>';
        }

        echo '<div id="content">
                <p>
                  <h1>Kyma API Configuration</h1>
                  <h3>
                    Kyma is a cloud-native application development framework.It provides the last mile capabilities that a developer needs to build a cloud-native application using several open-source projects such as Kubernetes, Istio, NATS, Kubeless, and Prometheus, to name a few. It is designed natively on Kubernetes and, therefore, it is portable to all major cloud providers.
                  </h3>
                  <h4>Use form below to connect Your WordPress application to Kyma.</h4>
                </p>

                <form action="'.esc_url(admin_url("admin-post.php")).'" method="post">
        	         <label for="provider">Provider name</label>
                   </br>
                   <input name="provider" id="provider" value="WordPress" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="name">API name</label>
                   </br>
                   <input name="name" id="name" value="wordpress-api" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="description">Description</label>
                   </br>
                   <input name="description" id="description" value="Wordpress API" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="targetUrl">Target URL</label>
                   </br>
                   <input name="targetUrl" id="targetUrl" value="http://192.168.64.1:8000/wp-json/wp/v2" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_title">Specification title</label>
                   </br>
                   <input name="spec_title" id="spec_title" value="wp-events" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_version">Specification version</label>
                   </br>
                   <input name="spec_version" id="spec_version" value="v2" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_description">Specification description</label>
                   </br>
                   <input name="spec_description" id="spec_description" value="WP API v2" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="host">Host</label>
                   </br>
                   <input name="host" id="host" value="192.168.64.1:8000" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="base_path">Base path</label>
                   </br>
                   <input name="base_path" id="base_path" value="/wp-json/wp/v2" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="api">Register api (with endpoint for delete a comment as example)</label>
                   </br>
                   <textarea style="margin-bottom:15px;margin-top:5px" rows="8" cols="50" name="api" id="api">"/comments/{commentId}":{"delete":{"consumes":["application/json"],"produces":["application/xml","application/json"],"parameters":[{"name":"commentId","in":"path","description":"commentId","required":true,"type":"string"}]}}</textarea>
                   <input type="hidden" name="action" value="api_registration_form">
                   <br/>
                   <input class="button-primary" type="submit" value="Register API">
                 </form>
            </div>';
            }
        }

new kyma_api_options_page;


function api_registration_call() {
    $url = 'http://metadata-service-external-api:8081/ec-default/v1/metadata/services';
    $registration_body = build_api_registartion_body($_POST);

    $response = wp_remote_post(
        $url,
        array( 'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => $registration_body, 'method' => 'POST' ));

    $redirectUrl = 'options-general.php?page=kyma_api_registration&registered=';

    if(is_wp_error($response)){
        $redirectUrl = $redirectUrl.'false';
    }else{
        $redirectUrl = $redirectUrl.'true';
    }

    wp_redirect(admin_url($redirectUrl));
}

function  build_api_registartion_body($registration_form){
   $registration_body='{"provider":"'.$registration_form['provider'].'","name":"'.$registration_form['name'].'","description":"'.$registration_form['description'].'","api":{"targetUrl":"'.$registration_form['targetUrl'].'","spec":{"swagger":"2.0","info":{"description":"'.$registration_form['spec_description'].'","version":"'.$registration_form['spec_version'].'","title":"'.$registration_form['spec_title'].'"},"host":"'.$registration_form['host'].'","basePath":"'.$registration_form['base_path'].
	 '","produces":["application/xml","application/json"],"paths":{'.stripslashes($registration_form['api']).'}}}}';
   return $registration_body;
}

add_action( 'admin_post_api_registration_form', 'api_registration_call' );
