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
    window.addEventListener( 'message', function( e ) {
        var d = e.data;
        if( !d ) return;

        // respond to ping from parent (handshake recovery)
        if( d.type === 'MAP_IAB_BRIDGE_PING' ) {
            e.source.postMessage( { type: 'MAP_IAB_BRIDGE_READY' }, e.origin === 'null' ? '*' : e.origin );
            return;
        }

        if( d.type !== 'MAP_IAB_BRIDGE' ) return;
        var reply = { type: 'MAP_IAB_BRIDGE_REPLY', id: d.id };
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
    // signal the parent frame that the bridge is ready (spontaneous on load)
    if( window.parent !== window ) {
        window.parent.postMessage( { type: 'MAP_IAB_BRIDGE_READY' }, '*' );
    }
})();
</script>
</body></html>
