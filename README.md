# wordpress-connector

## Overview

Project wordpress-connector was created to enable easy communication between WordPress and Kyma. With this project set up, the user is ready to make their first API registration from the WordPress admin panel and start using WordPress events based on [hooks](https://developer.wordpress.org/reference/hooks/) with Kyma.

## Requirements

The installation requires a working Basic Authentication solution for the [REST API](https://developer.wordpress.org/rest-api/). The default [Basic-Auth](https://github.com/WP-API/Basic-Auth) plugin from WordPress is providing it. If there are any problems with the plugin, maybe a patched version (https://github.com/eventespresso/Basic-Auth) will solve the problem.

## Installation

As the plugin is not part of the [WordPress Plugin Directory](https://wordpress.org/plugins/), it has to be installed manually.

1. Download the Plugin as a zipfile from GitHub.
2. Navigate in WordPress to `Plugins->Add New->Upload Plugin` and upload the zipfile.
3. Activate the Plugin

## Connect Kyma

1. Navigate to `Settings->Kyma`
2. Copy Application Connection URL from Kyma into `Kyma Connection` and click `Connect`

This will register all existing API's and configured events in Kyma.

## Known Issues

The Plugin was developed to showcase the functionality of the Kyma Application Connector with an open-source application. So far it is not well tested and might contain bugs. Feedback and pullrequests to improve the plugin are highly appreciated.
