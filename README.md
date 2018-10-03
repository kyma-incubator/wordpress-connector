# wordpress-connector
## Overview
Project wordpress-connector was created to enable easy communication between Wordpress and Kyma. With this project set up, user is ready to make first API registration from Wordpress admin panel and start using Wordpress events with Kyma.
## How to set up
To have fully functioning system, user have to start with installed Kyma - each step to install Kyma is described on official Kyma repository https://github.com/kyma-project/kyma. Furthermore - connection between Wordpress and Kyma is making use of Kyma Wormhole - application for connecting with Kyma clusters. Detailed description along with steps to install could be found on official repository https://github.com/kyma-incubator/wormhole (or repository with Kyma Wormhole adjusted to local development https://github.com/bartnie/wormhole). With Kyma and Kyma Wormhole set up, all what is left is to run composed prepared docker images. To do that, go to directory with docker-compose.yml file (root directory in repository) and run command
```
docker-compose up
```
Through that command docker will automatically run containers with Wordpress and MySQL prepared to work with Kyma.
## How to use
To access Wordpress instance go to http://localhost:8000. After configuring administrator account, go to administrator panel -> Settings -> Kyma Configuration. The form there serves as Kyma registration template - after completition, user should see bar with message "Wordpress API was registered in Kyma". 

Now to check registred API, visit https://console.kyma.local, log in with default credentials 'admin@kyma.cx' / 'nimda123', pick any of environments and go to Service Catalog -> Catalog. To bind it with environment, pick the registered API from service catalog and pick "Add to your Environment". Now go to your environment pick "Show All Remote Environments", then "ec-default" and "Create Binding". Choose one environment from list. 

To create lambda pick environment, go to Development -> Lambdas and choose "Add lambda". Files wordpress-comment-lambda.js and wordpress-user-lambda.js contains sample lambda body, which could be used here. You also have to set function trigger - "Select Function Trigger" -> "Event Trigger". Select all the event you want, save the lambda and enjoy your Wordpress - Kyma communication!
