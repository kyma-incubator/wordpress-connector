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
              },
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


class kyma_options_page {

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_menu() {
        add_options_page(
            'Kyma Configuration',
            'Kyma Configuration',
            'manage_options',
            'kyma_registration',
            array(
                $this,
                'kyma_page'
            )
        );
    }

function  kyma_page() {
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
                  <h1>Kyma Configuration</h1>
                  <h3>
                    Kyma is a cloud-native application development framework.It provides the last mile capabilities that a developer needs to build a cloud-native application using several open-source projects such as Kubernetes, Istio, NATS, Kubeless, and Prometheus, to name a few. It is designed natively on Kubernetes and, therefore, it is portable to all major cloud providers.
                  </h3>
                  <h4>Use form below to connect Your WordPress application to Kyma.</h4>
                </p>

                <form action="'.esc_url(admin_url("admin-post.php")).'" method="post">
        	         <label for="provider">Provider name</label>
                   </br>
                   <input name="provider" id="provider" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="name">API name</label>
                   </br>
                   <input name="name" id="name" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="description">Description</label>
                   </br>
                   <input name="description" id="description" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_asyncapi">Specification asyncapi</label>
                   </br>
                   <input name="spec_asyncapi" id="spec_asyncapi" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_title">Specification title</label>
                   </br>
                   <input name="spec_title" id="spec_title" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_version">Specification version</label>
                   </br>
                   <input name="spec_version" id="spec_version" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="spec_description">Specification description</label>
                   </br>
                   <input name="spec_description" id="spec_description" style="margin-bottom:15px;margin-top:5px">

                   </br>
                   <label for="events">Register events (with user created and comment post event as example)</label>
                   </br>
                   <textarea style="margin-bottom:15px;margin-top:5px" rows="8" cols="50" name="events" id="events">"user.created.v1":{"subscribe":{"summary":"User Register Event v1","payload":{"type":"object","required":["userId"],"properties":{"userId":{"type":"string","description":"Id of a User","title":"User uid"}}}}},"comment.post.v1":{"subscribe":{"summary":"Comment Post Event v1","payload":{"type":"object","required":["commentId"],"properties":{"commentId":{"type":"string","description":"Unique id of a comment","title":"Comment id"},"commentAuthorEmail":{"type":"string","description":"Email of an author","title":"Authors email"},"commentContent":{"type":"string","description":"Content of a comment","title":"Comment content"}}}}}</textarea>
                   <input type="hidden" name="action" value="api_registration_form">
                   <br/>
                   <input class="button-primary" type="submit" value="Register API">
                 </form>
            </div>';
            }
        }

new kyma_options_page;


function api_registration_call() {
    $url = 'http://metadata-service-external-api:8081/ec-default/v1/metadata/services';
    $registration_body = build_registartion_body($_POST);

    $response = wp_remote_post(
        $url,
        array( 'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => $registration_body, 'method' => 'POST' ));

    $redirectUrl = 'options-general.php?page=kyma_registration&registered=';

    if(is_wp_error($response)){
        $redirectUrl = $redirectUrl.'false';
    }else{
        $redirectUrl = $redirectUrl.'true';
    }

    wp_redirect(admin_url($redirectUrl));
}

function  build_registartion_body($registration_form){
   $registration_body = '{"provider":"'.$registration_form['provider'].'","name":"'.$registration_form['name'].'","description":"'.$registration_form['description'].'","events":{"spec":{"asyncapi":"'.$registration_form['spec_asyncapi'].'","info":{"title":"'.$registration_form['spec_title'].'","version":"'.$registration_form['spec_version'].'","description":"'.$registration_form['spec_description'].'"},"topics":{'.stripslashes($registration_form['events']).'}}}}';
   return $registration_body;
}

add_action( 'admin_post_api_registration_form', 'api_registration_call' );
