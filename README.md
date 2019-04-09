# wordpress-connector

## Overview

Project wordpress-connector was created to enable easy communication between Wordpress and Kyma. With this project set up, user is ready to make first API registration from Wordpress admin panel and start using Wordpress events based on [hooks](https://developer.wordpress.org/reference/hooks/) with Kyma.

## Requirements

The installation requires a working basic auth solution for the json API. The default [Basic-Auth](https://github.com/WP-API/Basic-Auth) plugin from wordpress is providing it. If there are any problems with the plugin, maybe a patched version (https://github.com/eventespresso/Basic-Auth) will solve the problem.

## Installation

As the plugin is not part of the Wordpress Plugin Marketplace, it has to eb installed manual.

1. Download the Plugin as a zipfile form github.
2. Navigate in Wordpress to `Plugins->Add New->Upload Plugin` and upload the zipfile.
3. Activate the Plugin

## Connect Kyma

1. Naviage to `Settings->Kyma`
2. Copy Application Connection URL from Kyma into `Kyma Connection` and klick `Connect`

This will register all existing API's and configured Events in kyma.

## Known Issues

The Plugin was developed to showcase the functionality of the Kyma Application Connector with a opensource application. So far it is not well tested and might dedicated to bugs. Feedback and pullrequests to improve the pulgin are highly appreciated.