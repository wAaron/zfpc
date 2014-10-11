<?php
//require_once 'SessionHandler.php';

// Define path to application directory
defined( 'ROOT_PATH' )
	|| define( 'ROOT_PATH', realpath( dirname( __FILE__ ) ) );
defined( 'APPLICATION_PATH' )
	|| define( 'APPLICATION_PATH', realpath( dirname( __FILE__ ) . '/application' ) );
defined( 'PUBLIC_FILE_PATH' )
	|| define( 'PUBLIC_FILE_PATH', realpath( dirname( __FILE__ ) . '/public/files' ) );

// Define application environment
defined( 'APPLICATION_ENV' )
	|| define( 'APPLICATION_ENV', ( getenv( 'APPLICATION_ENV' ) ? getenv( 'APPLICATION_ENV' ) : 'development' ) );

// Other definitions.
define( 'SECONDS_PER_DAY', 86400 );
define( 'SECONDS_PER_HOUR', 3600 );
define( 'LOCAL_DATETIME_FORMAT', 'Y-m-d H:i:s' );
define( 'LOCAL_DATE_FORMAT', 'Y-m-d' );

// Ensure library/ is on include_path
set_include_path( implode( PATH_SEPARATOR, array (
    realpath( APPLICATION_PATH . '/../library' )
    //get_include_path(),
) ) );
define( 'BASE_URL', '/' );

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run.
$application = new Zend_Application(
	APPLICATION_ENV,
	APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap()->run();
