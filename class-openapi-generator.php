<?php
namespace KymaProject\WordPressConnector;

/**
 * Highly Inspired by https://github.com/starfishmod/WPAPI-SwaggerGenerator/blob/master/lib/class-wp-rest-swagger-controller.php
 */
class OpenAPIGenerator {
    public function get_api_spec($title) {
        $host=preg_replace('/(^https?:\/\/|\/$)/','',site_url('/'));

        $apiPath =  get_rest_url();

        $basePath = preg_replace('#https?://#', '', $apiPath);
        $basePath = str_replace($host, '', $basePath);
        $basePath = preg_replace('#/$#', '', $basePath);
        
        $swagger = array(
			'swagger'=>'2.0'
			,'info'=>array(
				'version'=>'1.0'
				,'title'=>$title
			)
			,'host'=>$host
			,'basePath'=>$basePath
			,'schemes'=>array((is_ssl() | force_ssl_admin()) ? 'https' : 'http')
			,'consumes'=>array('multipart/form-data')
			,'produces'=>array('application/json')
			,'paths'=>array()
			,'definitions'=>array(
				'error'=>array(
					'properties'=>array(
						'code'=>array(
							'type'=>'string'
						)
						,'message'=>array(
							'type'=>'string'
						)
						,'data'=>array(
							'type'=>'object'
							,'properties'=>array(
								'status'=>array(
									'type'=>'integer'
								)
							)
						)
					)
				)
			)
			,'securityDefinitions'=>array(
				"basicAuth"=>array(
					"type"=> "basic"
				)
			)
        );
        
        $security = array(
            array('basicAuth'=>array())
        );

        $restServer = rest_get_server();

        foreach($restServer->get_routes() as $endpointName => $endpoint){
            $routeopt = $restServer->get_route_options( $endpointName );
            if(!empty($routeopt['schema'][1])){
                $schema = call_user_func(array(
                    $routeopt['schema'][0]
                    ,$routeopt['schema'][1])
                );
                $swagger['definitions'][$schema['title']]=$this->schemaIntoDefinition($schema);
                $outputSchema = array('$ref'=>'#/definitions/'.$schema['title']);
            }else{
                //if there is no schema then it's a safe bet that this API call 
                //will not work - move to the next one.
                continue;
            }

            $defaultidParams = array();
            //Replace endpoints var and add to the parameters required
            $endpointName = preg_replace_callback(
                '#\(\?P<(\w+?)>.*?\)#',
                function ($matches) use (&$defaultidParams){
                    $defaultidParams[]=array(
                            'name'=>$matches[1]
                            ,'type'=>'string'
                            ,'in'=>'path'
                            ,'required'=>true
                        );
                    return '{'.$matches[1].'}';
                },
                $endpointName
            );
            $endpointName = str_replace(site_url(), '',rest_url($endpointName));
            $endpointName = str_replace($basePath, '',$endpointName);
            
            if(empty($swagger['paths'][$endpointName])){
                $swagger['paths'][$endpointName] = array();
            }

            foreach($endpoint as $endpointPart){
			
                foreach($endpointPart['methods'] as $methodName=>$method){
                    if(in_array($methodName,array('PUT','PATCH')))continue; //duplicated by post
                    
                    $parameters = $defaultidParams;
                    
                    //Clean up parameters
                    foreach ($endpointPart['args'] as $pname=>$pdetails){
                        
                        $parameter=array(
                            'name'=>$pname
                            ,'type'=>'string'
                            ,'in'=>$methodName=='POST'?'formData':'query'
                        );
                        if(!empty($pdetails['description']))$parameter['description']=$pdetails['description'];
                        if(!empty($pdetails['format']))$parameter['format']=$pdetails['format'];
                        if(!empty($pdetails['default']))$parameter['default']=$pdetails['default'];
                        if(!empty($pdetails['enum']))$parameter['enum']=$pdetails['enum'];
                        if(!empty($pdetails['required']))$parameter['required']=$pdetails['required'];
                        if(!empty($pdetails['minimum'])){
                            $parameter['minimum']=$pdetails['minimum'];
                            $parameter['format']='number';
                        }
                        if(!empty($pdetails['maximum'])){
                            $parameter['maximum']=$pdetails['maximum'];
                            $parameter['format']='number';
                        }
                        if(!empty($pdetails['type'])){
                            if($pdetails['type']=='array'){
                                $parameter['type']=$pdetails['type'];
                                $parameter['items']=array('type'=>'string');
                            }elseif($pdetails['type']=='object'){
                                $parameter['type']='string';
                            
                            }elseif($pdetails['type']=='date-time'){
                                $parameter['type']='string';
                                $parameter['format']='date-time';
                            }else{
                                $parameter['type']=$pdetails['type'];
                            }
                        }
                        
                        $parameters[]=$parameter;
                    }
                    
                    //If the endpoint is not grabbing a specific object then 
                    //assume it's returning a list
                    $outputSchemaForMethod = $outputSchema;
                    if($methodName=='GET' && !preg_match('/}$/',$endpointName)){
                        $outputSchemaForMethod = array(
                            'type'=>'array'
                            ,'items'=>$outputSchemaForMethod
                        );
                    }
                    
                    $responses = array(
                        200=>array(
                            'description'=> "successful operation"
                            ,'schema'=>$outputSchemaForMethod
                        )
                        ,'default'=>array(
                            'description'=> "error"
                            ,'schema'=>array('$ref'=>'#/definitions/error')
                        )
                    );
                    
                    if(in_array($methodName,array('POST','PATCH','PUT')) && !preg_match('/}$/',$endpointName)){
                        //This are actually 201's in the default API - but joy of joys this is unreliable
                        $responses[201] = array(
                            'description'=> "successful operation"
                            ,'schema'=>$outputSchemaForMethod
                        );
                    }
                    
                    $operationId = ucfirst(strtolower($methodName)) . array_reduce(explode('/', preg_replace("/{(\w+)}/", 'by/${1}', $endpointName)), array($this, "compose_operation_name"));
                    $swagger['paths'][$endpointName][strtolower($methodName)] = array(
                        'parameters'=>$parameters
                        ,'security'=>$security
                        ,'responses'=>$responses
                        ,'operationId'=>$operationId
                    );
                    
                }
            }
        }

        return json_encode($swagger);
        

    }

    private function schemaIntoDefinition($schema){
        if(!empty($schema['$schema']))unset($schema['$schema']);
        if(!empty($schema['title']))unset($schema['title']);
        foreach($schema['properties'] as $name=>&$prop){
                        
            if(!empty($prop['properties'])){
                $prop = $this->schemaIntoDefinition($prop);
            } else if(isset($prop['properties'])){
                $prop['properties'] = new \stdClass();
            }
            
            //-- Changes by Richi
            if(!empty($prop['enum'])){
                if($prop['enum'][0] == ""){
                    if(count($prop['enum']) > 1){
                        array_shift($prop['enum']);	
                    }else{
                        $prop['enum'][0] = "NONE";
                    }
                };
            }
            if(!empty($prop['default']) && $prop['default'] == null){
                unset($prop['default']);
            }
//--
            
            if($prop['type']=='array'){
                $prop['items']=array('type'=>'string');
            }else			
            if($prop['type']=='date-time'){
                $prop['type']='string';
                $prop['format']='date-time';
            }
//			else if(!empty($prop['context']) && $prop['format']!='date-time'){
//				//$prop['enum']=$prop['context'];
//				
//			}
            if(isset($prop['required']))unset($prop['required']);
            if(isset($prop['readonly']))unset($prop['readonly']);
            if(isset($prop['context']))unset($prop['context']);
            
            
        }
        
        
        return $schema;
    }

    private function compose_operation_name($carry, $part) {
        $carry .= ucfirst(strtolower($part));
        return $carry;
    }

}
