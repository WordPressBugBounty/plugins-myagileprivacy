<?php
// IAB TCF localStorage bridge — no WP bootstrap.
// Served inside a hidden iframe on the root cookie domain.
// Accepts postMessage read/write/remove commands from any subdomain;
// replies so the parent frame can share consent data across subdomains.
header( 'Content-Type: text/html; charset=utf-8' );
header_remove( 'X-Frame-Options' );
header( 'Content-Security-Policy: frame-ancestors *' );
header( 'Cache-Control: no-store' );
?><!DOCTYPE html>
<html><head><meta charset="utf-8"></head><body>
<script>
(function() {
    var KEY_PREFIX = 'MAP_';                       // only this plugin's keys are exposed
    var BASE_HOST  = ( location.hostname || '' ).toLowerCase().replace( /^\.+/, '' );

    // Allow only the bridge host or its subdomains; reject third-party origins.
    function originAllowed( origin ) {
        if( !origin || origin === 'null' ) { return false; }
        var m = /^([a-z][a-z0-9+.\-]*):\/\/([^\/:?#]+)(?::\d+)?$/i.exec( origin );
        if( !m ) { return false; }
        if( m[1].toLowerCase() + ':' !== location.protocol.toLowerCase() ) { return false; } // block http-downgrade framing
        var host = m[2].toLowerCase();
        if( !BASE_HOST ) { return false; }
        return host === BASE_HOST ||
               ( host.length > BASE_HOST.length &&
                 host.slice( -( BASE_HOST.length + 1 ) ) === '.' + BASE_HOST );
    }

    function keyAllowed( k ) {
        return typeof k === 'string' && k.lastIndexOf( KEY_PREFIX, 0 ) === 0;
    }

    window.addEventListener( 'message', function( e ) {
        if( !originAllowed( e.origin ) ) { return; }   // origin gate: reject third parties
        var d = e.data;
        if( !d ) return;

        // respond to ping from parent (handshake recovery)
        if( d.type === 'MAP_IAB_BRIDGE_PING' ) {
            e.source.postMessage( { type: 'MAP_IAB_BRIDGE_READY' }, e.origin );
            return;
        }

        if( d.type !== 'MAP_IAB_BRIDGE' ) return;
        var reply = { type: 'MAP_IAB_BRIDGE_REPLY', id: d.id };
        if( !keyAllowed( d.key ) ) {                   // key gate: only MAP_ keys
            reply.error = 'forbidden_key';
            e.source.postMessage( reply, e.origin );
            return;
        }
        try {
            if( d.action === 'read' ) {
                reply.value = localStorage.getItem( d.key );
            } else if( d.action === 'write' ) {
                localStorage.setItem( d.key, d.value );
                reply.ok = true;
            } else if( d.action === 'remove' ) {
                localStorage.removeItem( d.key );
                reply.ok = true;
            }
        } catch( err ) {
            reply.error = err.message;
        }
        e.source.postMessage( reply, e.origin );
    } );
    // signal the parent frame that the bridge is ready (spontaneous on load);
    // carries no data, the actual exchange stays gated by originAllowed()
    if( window.parent !== window ) {
        window.parent.postMessage( { type: 'MAP_IAB_BRIDGE_READY' }, '*' );
    }
})();
</script>
</body></html>
