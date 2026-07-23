<?php
if( !defined( 'MAP_PLUGIN_NAME' ) )
{
    exit('Not allowed.');
}

/**
 * Generic, singleton notice manager.
 * Notices are persisted in the MAP_PLUGIN_PENDING_NOTICES WP option so they
 * survive page reloads until explicitly removed programmatically or by the user
 * clicking the dismiss (X) button.
 *
 * API: instance(), add(), remove(), has(), get(), get_all(), clear_all()
 *
 * Registration pattern: callers add()/remove() on admin_init based on current
 * state. The manager renders whatever is in the option on every admin page load.
 */
final class MAP_Admin_Notice_Manager {

    private static $instance = null;

    private function __construct() {
        add_action( 'admin_notices',             array( $this, 'render_all' ) );
        add_action( 'admin_head',                array( $this, 'render_inline_css' ) );
        add_action( 'admin_enqueue_scripts',     array( $this, 'enqueue_assets' ) );
        add_action( 'admin_footer',              array( $this, 'render_inline_js' ) );
        add_action( 'wp_ajax_map_remove_notice', array( $this, 'ajax_remove' ) );
    }

    /**
     * Returns the singleton instance, creating it on first call.
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Adds or overwrites a notice in persistent storage.
     *
     * @param string $id    Unique notice identifier (will be sanitize_key'd).
     * @param array  $args  {
     *   string   type        'info'|'success'|'warning'|'error'  (default 'info')
     *   string   message     HTML-safe string (wp_kses_post applied on render)
     *   bool     dismissible Show the X button (default true)
     *   array    actions     Each: { label, ajax_action, nonce_action, classes }
     *   string   capability  Min cap to see notice (default 'manage_options')
     *   array    screens     WP screen IDs where notice appears (empty = all)
     * }
     */
    public function add( $id, $args ) {
        $id = sanitize_key( $id );
        if ( '' === $id ) { return; }

        $defaults = array(
            'type'        => 'info',
            'message'     => '',
            'dismissible' => true,
            'actions'     => array(),
            'capability'  => 'manage_options',
            'screens'     => array(),
        );
        $args = array_merge( $defaults, $args );

        $notices        = $this->get_all();
        $notices[ $id ] = $args;
        update_option( MAP_PLUGIN_PENDING_NOTICES, $notices, false );
    }

    /**
     * Removes a notice from persistent storage.
     */
    public function remove( $id ) {
        $id      = sanitize_key( $id );
        $notices = $this->get_all();
        if ( isset( $notices[ $id ] ) ) {
            unset( $notices[ $id ] );
            update_option( MAP_PLUGIN_PENDING_NOTICES, $notices, false );
        }
    }

    /**
     * Returns true when a notice with the given id is in storage.
     */
    public function has( $id ) {
        $id      = sanitize_key( $id );
        $notices = $this->get_all();
        return isset( $notices[ $id ] );
    }

    /**
     * Returns the args array for a notice, or null if absent.
     */
    public function get( $id ) {
        $id      = sanitize_key( $id );
        $notices = $this->get_all();
        return isset( $notices[ $id ] ) ? $notices[ $id ] : null;
    }

    /**
     * Returns the full map of stored notices: { id => args }.
     */
    public function get_all() {
        $notices = get_option( MAP_PLUGIN_PENDING_NOTICES, array() );
        return is_array( $notices ) ? $notices : array();
    }

    /**
     * Removes all notices from storage.
     */
    public function clear_all() {
        update_option( MAP_PLUGIN_PENDING_NOTICES, array(), false );
    }

    // ── Rendering ─────────────────────────────────────────────────────────────

    /**
     * Outputs all stored notices that pass capability and screen filters.
     * Hooked to admin_notices.
     */
    public function render_all() {
        $notices = $this->get_all();
        if ( empty( $notices ) ) { return; }

        $screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $screen_id = is_object( $screen ) ? $screen->id : '';

        $allowed_types = array( 'info', 'success', 'warning', 'error' );
        $fox_url       = plugins_url( 'admin/img/fox-profile.png', MAP_PLUGIN_FILENAME );

        foreach ( $notices as $id => $args ) {
            $capability = isset( $args['capability'] ) ? $args['capability'] : 'manage_options';
            if ( ! current_user_can( $capability ) ) { continue; }

            if ( ! empty( $args['screens'] ) && '' !== $screen_id ) {
                if ( ! in_array( $screen_id, $args['screens'], true ) ) { continue; }
            }

            $type        = isset( $args['type'] )        ? $args['type']        : 'info';
            $message     = isset( $args['message'] )     ? $args['message']     : '';
            $dismissible = isset( $args['dismissible'] ) ? (bool) $args['dismissible'] : true;
            $actions     = isset( $args['actions'] )     ? $args['actions']     : array();

            if ( ! in_array( $type, $allowed_types, true ) ) {
                $type = 'info';
            }

            // Outer: WP notice anchor (for correct positioning) + flex row.
            echo '<div'
                . ' class="notice mapn-wrap mapn-wrap--' . esc_attr( $type ) . '"'
                . ' data-map-notice-id="' . esc_attr( $id ) . '"'
                . ' data-map-dismissible="' . ( $dismissible ? '1' : '0' ) . '"'
                . '>';

            // Fox avatar — sits outside the card, left side.
            echo '<div class="mapn-fox">'
                . '<img src="' . esc_url( $fox_url ) . '" alt="" aria-hidden="true">'
                . '</div>';

            // Card — styled box that contains message, actions, dismiss button.
            echo '<div class="mapn-card">';

            echo '<div class="mapn-msg">' . wp_kses_post( $message ) . '</div>';

            if ( ! empty( $actions ) ) {
                echo '<div class="mapn-actions">';
                foreach ( $actions as $action_item ) {
                    if ( ! isset( $action_item['ajax_action'], $action_item['nonce_action'] ) ) { continue; }
                    $label        = isset( $action_item['label'] )   ? $action_item['label']   : '';
                    $ajax_action  = $action_item['ajax_action'];
                    $nonce_action = $action_item['nonce_action'];
                    $btn_classes  = isset( $action_item['classes'] ) ? $action_item['classes'] : 'button';
                    $nonce        = wp_create_nonce( $nonce_action );

                    echo '<button type="button"'
                        . ' class="' . esc_attr( $btn_classes ) . '"'
                        . ' data-action="' . esc_attr( $ajax_action ) . '"'
                        . ' data-nonce="' . esc_attr( $nonce ) . '"'
                        . '>' . esc_html( $label ) . '</button> ';
                }
                echo '</div>'; // .mapn-actions
            }

            // Dismiss sits inside the card, absolutely positioned top-right.
            if ( $dismissible ) {
                $dismiss_nonce = wp_create_nonce( 'map_remove_notice' );
                echo '<button type="button"'
                    . ' class="mapn-close"'
                    . ' data-notice-id="' . esc_attr( $id ) . '"'
                    . ' data-nonce="' . esc_attr( $dismiss_nonce ) . '"'
                    . ' aria-label="' . esc_attr__( 'Dismiss this notice.', 'MAP_txt' ) . '"'
                    . '>&#x2715;</button>';
            }

            echo '</div>'; // .mapn-card
            echo '</div>'; // .mapn-wrap
        }
    }

    /**
     * Reserved for future asset enqueuing. JS is inline via render_inline_js(),
     * CSS is inline via render_inline_css().
     * Hooked to admin_enqueue_scripts.
     */
    public function enqueue_assets() {}

    /**
     * Prints the CSS for the fox-hero notice layout.
     * Only outputs when notices are present. Hooked to admin_head.
     */
    public function render_inline_css() {
        if ( empty( $this->get_all() ) ) { return; }
        echo '<style>'
            // Outer: WP notice anchor reset + flex row (avatar + card side by side).
            . '.mapn-wrap{'
                . 'display:flex!important;'
                . 'align-items:center!important;'
                . 'gap:12px;'
                . 'padding:0!important;'
                . 'border-left:none!important;'
                . 'background:transparent!important;'
                . 'box-shadow:none!important;'
                . 'margin-bottom:10px;'
            . '}'
            // Fox avatar: circular image, no grow.
            . '.mapn-fox{flex-shrink:0;}'
            . '.mapn-fox img{width:56px;height:56px;border-radius:50%;display:block;}'
            // Card: styled content box, mirrors .map_infobox look.
            . '.mapn-card{'
                . 'flex:1;'
                . 'position:relative;'
                . 'padding:10px 36px 10px 14px;'
                . 'background:#f4f8fb;'
                . 'border:1px solid #c5d7e8;'
                . 'border-radius:3px;'
                . 'min-width:0;'
            . '}'
            // Type-specific card colours.
            . '.mapn-wrap--success .mapn-card{background:#f0faf0;border-color:#7ec87e;}'
            . '.mapn-wrap--warning .mapn-card{background:#fef9e7;border-color:#d4bc47;}'
            . '.mapn-wrap--error   .mapn-card{background:#fdf0f0;border-color:#d6797a;}'
            // Message and actions.
            . '.mapn-msg{margin:0;}'
            . '.mapn-actions{margin-top:8px;}'
            . '.mapn-actions .button+.button{margin-left:6px;}'
            // Dismiss: top-right corner of the card.
            . '.mapn-close{'
                . 'position:absolute;'
                . 'top:8px;'
                . 'right:10px;'
                . 'background:none;'
                . 'border:none;'
                . 'cursor:pointer;'
                . 'color:#787c82;'
                . 'font-size:15px;'
                . 'line-height:1;'
                . 'padding:0;'
            . '}'
            . '.mapn-close:hover{color:#1d2327;}'
            . '</style>';
    }

    /**
     * Prints the vanilla JS handler for dismiss and action buttons.
     * Only outputs when notices are present. Hooked to admin_footer.
     */
    public function render_inline_js() {
        $notices = $this->get_all();
        if ( empty( $notices ) ) { return; }
        echo '<script type="text/javascript">' . $this->get_inline_js() . '</script>';
    }

    // ── AJAX handler ──────────────────────────────────────────────────────────

    /**
     * AJAX: remove a notice from storage. Hooked to wp_ajax_map_remove_notice.
     * Nonce param: 'security', action: 'map_remove_notice'.
     */
    public function ajax_remove() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'code' => 'forbidden' ), 403 );
        }
        check_ajax_referer( 'map_remove_notice', 'security' );

        $id = isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '';
        if ( '' === $id ) {
            wp_send_json_error( array( 'code' => 'missing_id' ) );
        }

        $this->remove( $id );
        wp_send_json_success();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Returns the vanilla JS that wires up dismiss and action buttons.
     * ajaxurl is the global WP admin variable — no PHP data injection needed.
     */
    private function get_inline_js() {
        return '(function(){' .
            'function mapAncestor(el,cls){' .
                'while(el&&el!==document){' .
                    'if(el.className&&(" "+el.className+" ").indexOf(" "+cls+" ")!==-1){return el;}' .
                    'el=el.parentNode;' .
                '}' .
                'return null;' .
            '}' .
            'function mapPost(action,data,cb){' .
                'var xhr=new XMLHttpRequest();' .
                'xhr.open("POST",ajaxurl,true);' .
                'xhr.setRequestHeader("Content-Type","application/x-www-form-urlencoded");' .
                'xhr.onreadystatechange=function(){if(4===xhr.readyState){cb(xhr);}};' .
                'var p="action="+encodeURIComponent(action);' .
                'for(var k in data){if(data.hasOwnProperty(k)){p+="&"+encodeURIComponent(k)+"="+encodeURIComponent(data[k]);}}' .
                'xhr.send(p);' .
            '}' .
            'function mapRemove(el){if(el&&el.parentNode){el.parentNode.removeChild(el);}}' .
            'document.addEventListener("DOMContentLoaded",function(){' .
                'var notices=document.querySelectorAll(".mapn-wrap");' .
                'for(var i=0;i<notices.length;i++){' .
                    '(function(notice){' .
                        'var db=notice.querySelector(".mapn-close");' .
                        'if(db){' .
                            'db.addEventListener("click",function(){' .
                                'var id=db.getAttribute("data-notice-id");' .
                                'var nonce=db.getAttribute("data-nonce");' .
                                'mapPost("map_remove_notice",{id:id,security:nonce},function(){mapRemove(notice);});' .
                            '});' .
                        '}' .
                        'var abs=notice.querySelectorAll("[data-action]");' .
                        'for(var j=0;j<abs.length;j++){' .
                            '(function(btn){' .
                                'btn.addEventListener("click",function(){' .
                                    'var action=btn.getAttribute("data-action");' .
                                    'var nonce=btn.getAttribute("data-nonce");' .
                                    'btn.disabled=true;' .
                                    'mapPost(action,{security:nonce},function(xhr){' .
                                        'var r={};' .
                                        'try{r=JSON.parse(xhr.responseText);}catch(e){}' .
                                        'if(r.success){' .
                                            'mapRemove(notice);' .
                                        '}else{' .
                                            'btn.disabled=false;' .
                                            'if(r.data&&r.data.action==="manual_download"&&r.data.download_url){' .
                                                'window.open(r.data.download_url,"_blank");' .
                                            '}' .
                                        '}' .
                                    '});' .
                                '});' .
                            '})(abs[j]);' .
                        '}' .
                    '})(notices[i]);' .
                '}' .
            '});' .
        '})();';
    }
}
