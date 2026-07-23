<?php

// Standalone CORS shim — no WP bootstrap.
// Serves JSON and IAB files from local-cache/my-agile-privacy/ with CORS headers.

if( $_SERVER['REQUEST_METHOD'] === 'OPTIONS' )
{
	header( 'Access-Control-Allow-Origin: *' );
	header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
	header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept' );
	header( 'Access-Control-Max-Age: 86400' );
	http_response_code( 204 );
	exit;
}

if( $_SERVER['REQUEST_METHOD'] !== 'GET' )
{
	http_response_code( 405 );
	header( 'Allow: GET, OPTIONS' );
	exit;
}

$f = isset( $_GET['f'] ) ? $_GET['f'] : '';

if( !preg_match( '/^(json\/)?[a-zA-Z0-9_\-]+\.json$/', $f ) )
{
	http_response_code( 400 );
	exit;
}

// all served files live inside the plugin under local-cache/my-agile-privacy/
$base_dir = realpath( dirname( dirname( __FILE__ ) ) . '/local-cache/my-agile-privacy' );

if( $base_dir === false )
{
	http_response_code( 503 );
	exit;
}

$target = realpath( $base_dir . DIRECTORY_SEPARATOR . $f );

// realpath defense: target must stay inside base_dir
if( $target === false || strncmp( $target, $base_dir . DIRECTORY_SEPARATOR, strlen( $base_dir ) + 1 ) !== 0 )
{
	http_response_code( 404 );
	exit;
}

if( !is_file( $target ) || !is_readable( $target ) )
{
	http_response_code( 404 );
	exit;
}

$mtime = filemtime( $target );
$size  = filesize( $target );
$etag  = sprintf( '"%x-%x"', $mtime, $size );

header( 'Access-Control-Allow-Origin: *' );
header( 'Vary: Origin' );
header( 'Content-Type: application/json; charset=utf-8' );
header( 'X-Content-Type-Options: nosniff' );
header( 'ETag: ' . $etag );
header( 'Cache-Control: public, max-age=3600' );

$inm = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( $_SERVER['HTTP_IF_NONE_MATCH'] ) : '';

if( $inm === $etag )
{
	http_response_code( 304 );
	exit;
}

header( 'Content-Length: ' . $size );
http_response_code( 200 );
readfile( $target );
