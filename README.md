# skeleton-core

## Description

This library contains the core functionality of Skeleton. This package
will receive the HTTP request and handles it.

The package will recognize 'applications' which are seperated parts of your
project. Each application implements a user interface and are defined by the
URL that is requested. For example one could create an application for a public
website and a second application for its CMS.
The application for the public website will listen on 'www.example.com' while
the CMS can be another application listening on 'admin.example.com'.

Based on the hostname, the library will search for the correct application.
The request will then be finalized by the requested module in the application.
If a media file is requested, based on the extension, the file will be served
from media/image, media/javascript, media/css in the application directory.

## Installation

Installation via composer:

    composer require tigron/skeleton-core

## Configuration

### Start

Initialize the application directory

    \Skeleton\Core\Config::$application_dir = $some_very_cool_directory;

From then, each subdirectory found will be seen as an '\Skeleton\Core\Application'.
Make sure an application follows the following directory structure

    - Application directory
        - config
            - Config.php
        - event
        - module
        - template
        - media
            - css
            - javascript
            - image

Add an additional asset directory.

		\Skeleton\Core\Config::$asset_dir = $my_frontend_library_directory;


### Config.php
This config file is application specific and can be found in the config-directory
of the application. It should have the following structure:

    <?php
    /**
     * App Configuration Class
     *
     * @author Gerry Demaret <gerry@tigron.be>
     * @author Christophe Gosiau <christophe@tigron.be>
     * @author David Vandemaele <david@tigron.be>
     */
    class Config_Admin extends Config {

    	/**
    	 * Config array
    	 *
    	 * @var array
    	 * @access private
    	 */
    	protected $config_data = [

    		/**
    		 * Hostnames
    		 */
    		'hostnames' => ['*'],

    		/**
    		 * base_uri
    		 */
    		'base_uri' => '',

    		/**
    		 * Default language. If no language is requested
    		 */
    		'default_language' => 'en',

    		/**
    		 * Routes
    		 */
    		'routes' => []
    	];
    }

The following options can be set in Config:

#### Hostnames
a comma seperated string of hostnames where this application should
listen for. Asterisk can be used to specify a wildcard. Example:

    [ 'admin.test.example.be', 'admin.*.example.be' ]

#### base_uri
Specifies the base uri for the application. If a base_uri is specified, it will
be included in the reverse url generation.
Example:

    '/admin'

## Howto

### Routing to the correct application

Based on the Host-header in the request, the correct application will be
searched for. The configuration of the application contains a section where
one or more hostnames can be defined. If the Host-header of the request matches
one of these hostnames, the request will be finalized by this application.

The hostnames configuration, can contain wildcards: '*'.
For example: '*.example.com' will match any subdomain of 'example.com'.

If your application has a different base_uri, it can be configured as described
above. For example: the application for a CMS can be seperated by setting
its 'base_uri' to '/admin'.

### Routing to the correct module

Requests that do not have a file extension will be routed towards a module and
a function. The module is determined based on the request uri excluding all
$_GET parameters. The module is a class that should be derived from
\Skeleton\Core\Web\Module.

This can be best explained with some examples:

| requested uri    | classname       | filename             |
| -------------    | ---------       | --------             |
| /user/management | Web_Module_User_Management | /user/management.php |
| /                | Web_Module_Index    | /index.php |
| /user            | Web_Module_User     | /user.php |
|                  | Web_Module_User_Index | /user/index.php' |

### Routing to the correct method

A module can contain multiple methods that can handle the request. Each of
those requests have a method-name starting with 'display'. The method is defined
based on the $_GET['action'] variable.

Some examples:

| requested uri    | classname           | method               |
| -------------    | ---------           | --------             |
| /user            | Web_Module_User     | display()			|
| /user?action=test| Web_Module_User     | display_test()       |

### Media

If the requested url contains an extension that is from a known media type,
the requested file is searched for in the media/* directory of the application.
If the requested media file is not found, skeleton-core will search for the media
in the Config::$asset_dir

### Events
Events can be created to perform a task on certain key-moments in the
application execution. Events are defined in Event context classes. These classes
are optionally but when needed, should be located in the 'event' directory. The
filename should be 'name_of_the_context.php' (ex: Application.php). The
classname should be located in namespace \App\YOUR_APP\Event\CONTEXT, with
CONTEXT being one of the available contexts:
    - Application
    - Module
    - Media

Example event class:

    <?php
    /**
     * Event
     *
     * @author Gerry Demaret <gerry@tigron.be>
     * @author Christophe Gosiau <christophe@tigron.be>
     * @author David Vandemaele <david@tigron.be>
     */

	namespace App\Admin\Event;

    class Module extends \Skeleton\Core\Event {

        /**
         * Access denied
         *
         * @access public
         */
        public function access_denied() {
            Session::redirect('/reset');
        }

    }


#### Application context

##### bootstrap

The bootstrap method is called before loading the application module.

    	public function bootstrap(\Skeleton\Core\Web\Module $module) { }

##### teardown

The teardown method is called after the last action of the application is done.

    	public function teardown(\Skeleton\Core\Web\Module $module) { }

The detect method is called on every request to determine if the application
should handle the request, based on the hostname and the $request_uri.
This event should return 'true' in order to proceed with this application.

    	public function detect($hostname, $request_uri) { }

#### Module context

##### access_denied

The module_access_denied is called whenever a module is requested that cannot be
accessed for the user. The secure()-method in the module indicates if the user
is granted.

    	public function access_denied(\Skeleton\Core\Web\Module $module) { }

##### not_found

The not_found is called whenever a modules is requested that does not exist

	    public function not_found() { }

#### Media context

##### not_found

The not_found is called whenever a media file is requested that cannot be found

	    public function not_found() { }

#### Error context

##### exception

The exception is called whenever an exception is not caught.

        public function exception() { }
