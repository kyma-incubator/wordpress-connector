# wordpress-connector
## Overview
Project wordpress-connector was created to enable easy communication between Wordpress and Kyma. With this project set up, user is ready to make first API registration from Wordpress admin panel and start using Wordpress events based on hooks(https://developer.wordpress.org/reference/hooks/) with Kyma.
## How to set up
To have fully functioning system, user has to start with installed Kyma - each step to install Kyma is described on official Kyma repository https://github.com/kyma-project/kyma. Furthermore - connection between Wordpress and Kyma is making use of Kyma Wormhole - application for connecting with Kyma clusters. Detailed description along with steps to install could be found on official repository https://github.com/kyma-incubator/wormhole (or repository with Kyma Wormhole adjusted to local development https://github.com/bartnie/wormhole - RECOMMENDED). Wormhole connector application should be run with `--http-mode` flag. Wormhole dispatcher application should be deployed in `kyma-integration` namespace, otherwise it won't be able to communicate with services responsibles for API registration and events.

With Kyma and Kyma Wormhole set up, all what is left is to run composed prepared docker images. To do that, go to directory with docker-compose.yml file (root directory in repository) and run command:
```
docker-compose up
```
Through that command docker will automatically run containers with Wordpress and MySQL prepared to work with Kyma.
## How to use
To access Wordpress instance go to http://localhost:8000. After configuring administrator account, go to administrator panel -> Settings -> Permalinks and change them to "Post name". To start Kyma configuration go to administrator panel -> Settings -> Kyma Events Configuration. The form there serves as template for register WordPress events in Kyma  - after completition, user should see green bar with message "Wordpress events was registered in Kyma". After events registration, user has to register WordPress API, to enable Kyma to communicate back. To do that go to administrator panel -> Settings -> Kyma API Configuration. After completition of this form, user should see bar with message "Wordpress API was registered in Kyma".

Now to check registred events and API, visit https://console.kyma.local, log in with default credentials 'admin@kyma.cx' / 'nimda123', pick any of environments and go to Service Catalog -> Catalog. To bind it with environment, pick the registered API from service catalog and pick "Add to your Environment". The same has to be done for events. Now go to your environment pick "Show All Remote Environments", then "ec-default" and "Create Binding". Choose one environment from list.

To create lambda pick your environment, go to Development -> Lambdas and choose "Add lambda". Files `wordpress-comment-lambda.js` and `wordpress-user-lambda.js` contains sample lambda body, which could be used here. The `wordpress-comment-lambda` function require one external dependency - it can be added in Dependency section. Dependency definition can be found in `wordpress-comment-dependencies.js` file. You also have to set function trigger - "Select Function Trigger" -> "Event Trigger". Select the corresponding event - `comment.post`, save the lambda and enjoy your Wordpress - Kyma communication!

## How it works
To test if whole communication is working user should add comment below one of the posts in WormPress application. If comment doesn't contain word `Kyma` lambda funtion created before will delete this comment using WordPress Rest API.


## Known issues
There are same issues with setting up tunnel between `wormhole-connector` and `wormhole-dispatcher` in kubernetes cluster evironment like GCP or Azure, so right now scenario works only on local cluster - minikube.

Sometimes after deployment there is a problem with accessing WordPress' posts. To fix this go to "Permalinks" section change it to "Plain", save and than change it back to "Post name".
