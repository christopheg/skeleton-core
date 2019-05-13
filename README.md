# skeleton-core

## Description

This library contains the core functionality of Skeleton.

## Installation

Installation via composer:

    composer require tigron/skeleton-core

## Howto

Initialize the application directory

    \Skeleton\Core\Config::$application_dir = $some_very_cool_directory;

From then, each subdirectory found will be seen as an '\Skeleton\Core\Application'.
Make sure an application follows the following directory structure

    - Application directory
        - config
            - Config.php
            - Hook.php
        - module
        - template
        - media

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

### Hook.php
This file is optionally, but when needed, it should be located in the
config-directory. The Hook class should have the following structure:

    <?php
    /**
     * Hooks
     *
     * @author Gerry Demaret <gerry@tigron.be>
     * @author Christophe Gosiau <christophe@tigron.be>
     * @author David Vandemaele <david@tigron.be>
     */

    class Hook_Admin {
    	/**
    	 * Bootstrap the application
    	 *
    	 * @access private
    	 */
    	public static function bootstrap(\Skeleton\Core\Web\Module $module) {

    	}

    	/**
    	 * Teardown of the application
    	 *
    	 * @access private
    	 */
    	public static function teardown(\Skeleton\Core\Web\Module $module) {
    		// Do your cleanup jobs here
    	}
    }

#### bootstrap

The bootstrap method is called before loading the application module.

    	public static function bootstrap(\Skeleton\Core\Web\Module $module) { }

The teardown method is called after the last action of the application is done.

    	public static function teardown(\Skeleton\Core\Web\Module $module) { }

The module_access_denied is called whenever a module is requested that cannot be
accessed for the user. The secure()-method in the module indicates if the user
is granted.

    	public static function module_access_denied(\Skeleton\Core\Web\Module $module) { }

The module_not_found is called whenever a modules is requested that does not exist

	    public static function module_not_found() { }

The media_not_found is called whenever a media file is requested that cannot be found

	    public static function media_not_found() { }
