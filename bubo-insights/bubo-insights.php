<?php

/**
 * @link              https://github.com/pizza2mozzarella/bubo_insights
 * @since             1.0.0
 * @package           bubo-insights
 * @wordpress-plugin
 * Plugin Name:       Bubo Insights
 * Plugin URI:        https://github.com/pizza2mozzarella/bubo_insights
 * Description:       Bubo Insights tracks and displays the most useful user navigation data without using cookies or violating privacy. Simple, useful, effective.
 * Version:           1.0.0
 * Author:            pizza2mozzarella
 * Author URI:        https://github.com/pizza2mozzarella/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bubo_insights
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'BUBO_INSIGHT_VERSION', '1.0.0' );

/* enqueuing jQuery... */
function add_jquery_for_bubo_insights() { 
    wp_enqueue_script( 'jquery' );
}    
add_action('init', 'add_jquery_for_bubo_insights');

/* enqueuing the logging scripts... */
function bubo_insights_tracking_scripts() {
    wp_register_script('bubo_insights_tracking_script', plugins_url('/public/js/bubo_insights.js', __FILE__), array('jquery'),'1.0.6', true);
    wp_enqueue_script('bubo_insights_tracking_script');
}
add_action( 'wp_enqueue_scripts', 'bubo_insights_tracking_scripts' );

/*plugin main page*/
function bubo_insights_main_page() {
	add_menu_page(
      __( 'Bubo Insights', 'my-textdomain' ),
      __( 'Bubo Insights', 'my-textdomain' ),
      'publish_pages',
      'bubo_insights',
      'bubo_insights_stats_page_contents',
      'dashicons-schedule',
      3
	);
}
add_action( 'admin_menu', 'bubo_insights_main_page' );

/* csv export function */
function bubo_insights_export_csv( $table = 'bubo_insights_event_log', $reportname = 'bubo_insights_event_log_backup', $timespan = '(true)', $select = '*', $filters = '(true)' ) {
    
    global $wpdb;

    $table_name = $wpdb->prefix . $table;
    $results = $wpdb->get_results("
        SELECT $select FROM $table_name 
        WHERE $filters AND $timespan
      	ORDER BY `id` DESC
    ");
	
	$csv = '';
	if( ! empty($results[0]) ){
		$csv .= implode(',' , array_keys(get_object_vars($results[0])));
	}
    $csv .= "\n"; // important! Make sure to use use double quotation marks.
    foreach( $results as $result ) {
        $csv .= implode(',' , get_object_vars($result));
        $csv .= "\n"; // important! Make sure to use use double quotation marks.
    }
    $date = date("YMd");
    $filename = $date . '_' . $reportname . '.csv';
    header( 'Content-Type: text/csv' ); // tells browser to download
    header( 'Content-Disposition: attachment; filename="' . $filename .'"' );
    header( 'Pragma: no-cache' ); // no cache
    header( "Expires: Sat, 01 Jan 1990 05:00:00 GMT" ); // expire date

    echo $csv;
    exit;
}

/* plugin settings page contents */
function bubo_insights_settings_page_contents() {
    
    // CSV export mode
    if( isset( $_GET['export'] ) ) {
        if($_GET['export']=='event_log_backup') {
            bubo_insights_export_csv( 'bubo_insights_event_log', $_GET['export'] );
        }
        if($_GET['export']=='visitors_log_backup') {
            bubo_insights_export_csv( 'bubo_insights_visitors_log', $_GET['export'] );
        }
    }
                    
    ?>  <style>
            body { background-color:#fafafa; }
            main { padding:30px 30px 0 10px; }
            h1,section { background-color:white; padding:25px; border-radius:5px; margin:0 0 15px 0; box-shadow: 2px 3px 3px 0px #bbb, inset 1px 1px 0px 0px #ddd; }
            h2 { margin-top:0; }
            .flex { display:flex; }
            .gap10 { gap:10px; }
            .control { display:flex; padding:10px 15px; border-radius:5px; color:white !important; font-weight:600; text-decoration:none !important; box-shadow:1px 2px 2px lightgray; border:unset; }
            .control:hover { box-shadow:1px 2px 1px lightgray; filter:saturate(1.2); }
            .control:active { transform: translateX(2px) translateY(2px); box-shadow:0px 1px 1px gray; }
            .blue { background-color: cornflowerblue; }
            .red { background-color: tomato; }
        </style>
        
        <main>

            <h1>Settings</h1>
            
            <section>
                <h2>Back up the database</h2>
                <p>Export this plugins' database tables in .csv</p>
                <div class="flex gap10" >
                    <a class="control blue" href="admin.php?page=bubo_insights_settings&export=event_log_backup&noheader=1">Event Log</a>
                    <a class="control blue" href="admin.php?page=bubo_insights_settings&export=visitors_log_backup&noheader=1">Visitors Log</a>
                </div>
            </section>

            <section>
                <h2>Danger Zone [WARNING: permanent actions]</h2>
                <p>This action remove ALL data collected by the plugin from this website database.</p>
                <p>This is useful when data collected is slowing the website or when uninstalling this plugin, otherwise not recommended.</p>
                <p>It's always a good idea to download a backup before performing this action!</p>
                <button id="purge" class="control red" >PURGE PLUGIN DATABASE</button>
            </section>
            
        </main>
        
        <script>
        
            var ajaxUrl = "<?php echo get_site_url(); ?>/wp-admin/admin-ajax.php";
            function drop_all_tables() {
                var action = 'bubo_insights_drop_all_tables';
                jQuery.ajax( ajaxUrl, {
                    method : "POST",
                    dataType : "json",
                    data : {action: action },
                    success: function(response) {
                        jQuery("#purge").html("PURGED!");
                        alert(response);
                    },
                    error: function(response) {
                        alert("Problems encountered, database has not been purged correctly!");			 
                    }
                });
            }
            
            jQuery(document).ready(function() {
                jQuery("#purge").on("click", function(e) {
                    if(confirm("Did you backup?")) {
                        if(confirm("Are you sure?\nThis can't be undone!")) {
                            drop_all_tables();
                        };
                    };
                });
            });
        </script>
        
    <?php
}

/* settings AJAX */
add_action('wp_ajax_bubo_insights_drop_all_tables', 'bubo_insights_drop_all_tables_callback');

function bubo_insights_drop_all_tables_callback() {
  if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    bubo_insights_drop_all_tables();
      
    $response = 'All tables dropped!';     
    $responseJSON = json_encode($response);
    echo $responseJSON;
    
  }
  else {   
  	header("Location: ".$_SERVER["HTTP_REFERER"]);  
  }
  die();
}

/* settings page*/
function bubo_insights_settings_page() {
	add_submenu_page(
      'bubo_insights',
      __( 'Settings', 'my-textdomain' ),
      __( 'Settings', 'my-textdomain' ),
      'publish_pages',
      'bubo_insights_settings',
      'bubo_insights_settings_page_contents',
      'dashicons-schedule',
      3
	);
}
add_action( 'admin_menu', 'bubo_insights_settings_page' );

/*stats page*/
function bubo_insights_stats_page() {
	add_submenu_page(
      'bubo_insights',
      __( 'Stats', 'my-textdomain' ),
      __( 'Stats', 'my-textdomain' ),
      'publish_pages',
      'bubo_insights',
      'bubo_insights_stats_page_contents',
      'dashicons-schedule',
      3
	);
}
add_action( 'admin_menu', 'bubo_insights_stats_page' );

/* bubo insights original hashing method chr_hash93 */
/* converts a low collision sha1 hexadecimal hash into a 10 character long hash code with 93 ascii characters (33to125), replaces " and ' and , with ~ (126) for compatibility and peace of mind */
function bubo_insights_chrhash93($input) {
    $chr_hash = '';
    $hash = sha1($input);
    for($i=0;$i<10;$i++) {
        $slice = intval(hexdec(substr($hash, $i*2 , 4)));
        $chr_hash .= chr(round($slice / 704.688)+32);
    }
    $chr_hash = str_replace('"', '~', $chr_hash);
    $chr_hash = str_replace("'", "~", $chr_hash);
    $chr_hash = str_replace(",", "~", $chr_hash);
    return $chr_hash;
}

/* event logging AJAX */
add_action('wp_ajax_bubo_insights_event_log', 'bubo_insights_loggedin_event_log_callback');
add_action('wp_ajax_nopriv_bubo_insights_event_log', 'bubo_insights_loggedout_event_log_callback');

function bubo_insights_loggedin_event_log_callback()  {
	bubo_insights_event_log_callback(1);
}
function bubo_insights_loggedout_event_log_callback() {
	bubo_insights_event_log_callback(0);
}

function bubo_insights_event_log_callback($loggedin)  {
	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		
		$user                       = md5($_SERVER['REMOTE_ADDR'].$_SERVER["HTTP_USER_AGENT"]);
		$event                      = array();
		$event['user']              = $user;
		$event['loggedin']          = $loggedin;
		$event['event']             = substr($_REQUEST['eventtype'], 0, 1);
		$event['eventtype']         = $_REQUEST['eventtype'];
		$event['eventtime']         = $_REQUEST['eventtime'];
		$event['referrer']          = $_REQUEST['referrer'];
		$event['origin']            = $_REQUEST['origin'];
		$event['elementcontent']    = $_REQUEST['elementcontent'];
		$event['elementtag']        = $_REQUEST['elementtag'];
		$event['elementclass']      = $_REQUEST['elementclass'];
		$event['link']              = $_REQUEST['link'];
		$visitor                    = array();
		$visitor['user']            = $user;
		$visitor['loggedin']        = $loggedin;
		$visitor['scale']           = $_REQUEST['scale'];
		$visitor['screenwidth']     = $_REQUEST['screenwidth'];
		$visitor['screenheight']    = $_REQUEST['screenheight'];
		if($_REQUEST['touchenabled'] == 'true') { $visitor['touchenabled'] = 1; } else { $visitor['touchenabled'] = 0; }
		
		$os_ua = $_SERVER["HTTP_USER_AGENT"];
		$open = strpos($os_ua, "(");
		$close = strpos($os_ua, ")");
		$ua_os = substr($os_ua, $open + 1, $close - $open - 1);
		
		$browser_ua = $_SERVER["HTTP_SEC_CH_UA"];
		$browser = str_replace( '\"', '"', $browser_ua );
		$browser = str_replace( ';v=', '' , $browser );
		$browser = str_replace( '"', '' , $browser );
		$browser = str_replace( ',', ';' , $browser );
		$ua_browser = preg_replace('/[0-9]+/', '', $browser);
		
		$lang_ua = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
		$lang = preg_replace('/[0-9]+/', '', $lang_ua);
		$lang = str_replace( 'q=.', '' , $lang );
		$lang = str_replace( ';,', ',' , $lang );
		$lang = str_replace( ';', '' , $lang );
		$ua_lang = str_replace( ',', '; ' , $lang );
		
		$mobile_ua = $_SERVER["HTTP_SEC_CH_UA_MOBILE"];
		$ua_mobile = str_replace( '?', '' , $mobile_ua );

		$visitor['ua_os']               = $ua_os;
		$visitor['ua_browser']          = $ua_browser;
		$visitor['ua_lang']             = $ua_lang;
		$visitor['ua_mobilerequest']    = $ua_mobile;
		
		$os_haystack = strtolower($ua_os);
		if( str_contains($os_haystack, "ipad")
			OR str_contains($os_haystack, "iphone")  
			OR str_contains($os_haystack, "mac") 
		) {
			$os = 'a';
		}
		elseif ( str_contains($os_haystack, "win") 
		) {
			$os = 'w';
		}
		elseif ( str_contains($os_haystack, "android")
				 OR str_contains($os_haystack, "linux")
				 OR str_contains($os_haystack, "cros")
		) {
			$os = 'u';
		}
		else {
			$os = '?';
		}
		$visitor['os']              = $os;
		$event['os']                = $os;
		
		$device_haystack = strtolower($ua_os);
		if( str_contains($os_haystack, "win")
			OR ( str_contains($os_haystack, "mac") AND ! str_contains($os_haystack, "iphone") AND ! str_contains($os_haystack, "ipad") )
			OR str_contains($os_haystack, "cros")
			OR ( str_contains($os_haystack, "linux") AND ! str_contains($os_haystack, "android") )
			OR $_REQUEST['touchenabled'] == 'false'
		) {
			$device = 'd';
			$device_ext = 'DESK';
		}
		elseif ( str_contains($os_haystack, "ipad")
				 OR ( str_contains($os_haystack, "android") AND ($ua_mobile = 0 OR $_REQUEST['scale'] < 2) )
				 OR $_REQUEST['screenwidth'] > $_REQUEST['screenheight']
				 
		) {
			$device = 't';
			$device_ext = 'TABL';
		}
		elseif ( str_contains($os_haystack, "iphone")
				 OR str_contains($os_haystack, "android")
				 OR str_contains($os_haystack, "windows phone")
		) {
			$device = 'm';
			$device_ext = 'MOBI';
		}
		else {
			$device = '?';
			$device_ext = 'UNKN';
		}
		$visitor['device']          = $device;
		$event['device']            = $device;
		
		$logged_status              = array(' ', '~');
		$user_hash                  = strtoupper(substr($ua_lang, 0, 2)) . strtolower(substr($ua_os, 0, 3))  . $device_ext . $logged_status[$loggedin] . bubo_insights_chrhash93($user);
		$event['user']              = $user_hash;
		$visitor['user']            = $user_hash;
		
		$event['pagesession']       = substr(bubo_insights_chrhash93($user_hash . $_REQUEST['origin'] . $_REQUEST['inittime']), 0 , 4);

		$event['sessionduration']   = $_REQUEST['sessiontime'];
	
	    if( $event['event'] == 'p' OR $event['event'] == 'c' ) {
			bubo_insights_eventlog_table_insert_record($event);
			bubo_insights_visitorslog_table_insert_record($visitor);
		}

		global $wpdb;    
		$table_name = $wpdb->prefix . 'bubo_insights_event_log';
		$condition = " ( user = '" . $user_hash . "'  AND event = 'p' AND pagesession = '" . $event['pagesession'] . "' ) ";
		$pagesession_id = $wpdb->get_results( "SELECT id FROM $table_name WHERE $condition ");
		
		$update = $wpdb->update(
					$table_name,
					array( 'sessionduration' => $_REQUEST['sessiontime']	),
					array( 'id' => intval($pagesession_id[0]->id) ),
					array( '%s'	)
		);
		
		ob_start();
	 
		$response = ob_get_clean(); 		
		 
		$responseJSON = json_encode($response);
		
		echo $responseJSON;
	}
	else {   
		header("Location: ".$_SERVER["HTTP_REFERER"]);  
	}
	die();
	}


/* new event logging */
function bubo_insights_eventlog_table_insert_record($row){
    global $wpdb;

    $table_name = $wpdb->prefix . 'bubo_insights_event_log';
    
    $columns = array(
        'event',
        'eventtype',
        'pagesession',
        'sessionduration',
        'eventtime',
        'user',
        'loggedin',
        'device',
        'os',
        'referrer',
        'origin',
        'elementcontent',
        'elementtag',
        'elementclass',
        'link',
    );
    $insert_array = array();
    foreach($columns as $column) {
       if(!empty($row[$column] OR $row[$column] == 0)) { $insert_array[$column] = $row[$column]; } 
    }

    $wpdb->insert(
        $table_name,
        $insert_array
    );
}

/* event log table in wp database */
function bubo_insights_eventlog_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bubo_insights_event_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event char(1),
        eventtype tinytext,
        pagesession varchar(4),
        sessionduration DECIMAL(9,2),
        eventtime int(11) unsigned,
        device varchar(10),
        os varchar(10),
        user tinytext,
        loggedin tinyint(4),
        referrer text,
        origin text,
        elementcontent text,
        elementtag tinytext,
        elementclass tinytext,
        link text,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bubo_insights_eventlog_table' );

/* new visitors logging */
function bubo_insights_visitorslog_table_insert_record($row){
    global $wpdb;

    $table_name = $wpdb->prefix . 'bubo_insights_visitors_log';

    $wpdb->insert(
        $table_name,
        array(
            'user'              => $row['user'],
            'loggedin'          => $row['loggedin'],
            'device'            => $row['device'],
            'os'                => $row['os'],
            'useragent'         => $row['useragent'],
            'lang'              => $row['lang'],
            'platform'          => $row['platform'],
            'scale'             => $row['scale'],
            'screenwidth'       => $row['screenwidth'],
            'screenheight'      => $row['screenheight'],
            'touchenabled'      => $row['touchenabled'],
            'ua_os'             => $row['ua_os'],
            'ua_browser'        => $row['ua_browser'],
            'ua_lang'           => $row['ua_lang'],
            'ua_mobilerequest'  => $row['ua_mobilerequest'],
        )
    );
}

/* visitors log table in wp database */
function bubo_insights_visitorslog_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bubo_insights_visitors_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user tinytext UNIQUE,
        loggedin tinyint(4),
        device varchar(10),
        os varchar(10),
        address tinytext,
        useragent text,
        lang tinytext,
        platform tinytext,
        scale int(2),
        screenwidth int(5),
        screenheight int(5),
        touchenabled int(2),
        ua_os tinytext,
        ua_browser tinytext,
        ua_lang tinytext,
        ua_mobilerequest tinyint(4),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bubo_insights_visitorslog_table' );

/* drop all tables */
function bubo_insights_drop_all_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'bubo_insights_event_log';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    
    $table_name = $wpdb->prefix . 'bubo_insights_visitors_log';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );   
}

/* admin pages */
/* stats page */
function bubo_insights_stats_page_contents() {
    ?>

    <style>
        @media (min-width:769px) {
            main { padding:15px 15px 0 15px; }
            main h1 , main section { padding:25px; border-radius:5px; margin:0 0 15px 0; box-shadow: 2px 3px 3px 0px #bbb, inset 1px 1px 0px 0px #ddd; }
            #stats { margin:0; }
        }
		main { padding-top:15px; }
        body { background-color:#fafafa; }
        .auto-fold #wpcontent { padding-left: 0; }
        h1,section { background-color:white; padding: 10px 20px; margin:0; }
        h1 { padding-left: 30px; }
        h2 { margin-top:0; }
        .flex { display:flex; }
        .gap10 { gap:10px; }
        .hidden { display:none !important; }
        .disabled { opacity:80%; filter: grayscale(0.80); }
        details { padding:5px 0 5px 0; width:100%; }
        summary { padding:0 0 5px 0; }
        .filters_summary { font-size:20px; font-weight:600; }
        .textinput ul.chosen-choices { border-radius:4px; border:1px solid #8c8f94; }
        .textinput input , .textinput select { padding-top:2px; padding-bottom:2px; }
        .inputs { display:flex; flex-wrap:wrap; align-items: flex-end; gap:5px; }
        .inputs .filterpanel { min-height: 30px; padding: 5px; }
        .textinput { position:relative; display:flex; }
        .inputmargin { margin:10px 5px; }
        .textinput .label { position: absolute; top:-10px; left:5px; background-color:white; padding:0 2px; font-size:13px; border-radius:10px; }
        #who , #when , #where { margin:0; flex-basis: 325px; flex-grow: 1; display:flex; flex-direction:column; align-items:stretch; }

        .who_tab { display:flex; gap:3px; margin:0 5px; }
        .who_tags { display:flex; flex-shrink:0; flex-wrap:wrap; align-items:center; position:relative; border:3px solid Coral; padding:5px; border-radius:7px; }
        .who_tags_label { border-radius:5px 5px 0 0; outline:1px solid Coral; padding:5px 7px; color:black; }
        .who_tags_label.selected { background-color:Coral; }
        .who_tags_label.selected::before { content:'Users\' '; }
        .who_tags_label::before { content:''; }        
        .who_tag { background-color: White; padding-left:5px; }
        
        .when_tab  { display:flex; gap:3px; margin:0 5px; }
        .whenmode_label { border-radius:5px 5px 0 0; outline:1px solid Burlywood; padding:5px 7px; color:black; }
        .whenmode_label.selected { background-color:Burlywood; }
        .whenmode_label.selected::after { content:' chart'; }
        .whenmode_label::after { content:''; }
        .whenmode { display:flex; flex-shrink:0; flex-wrap:wrap; align-items:center; position:relative; padding:5px; border-radius:7px; border: 3px solid Burlywood; }

        .where_tab  { display:flex; gap:3px; margin:0 5px; }
        .whereinput_label { border-radius:5px 5px 0 0; padding:0 5px; color:black; position: relative; padding:5px 7px; outline-width: 1px !important; }
        .whereinput_label:not(.selected) { background-color: white; }
        .whereinput_label.selected::after { content:' URL:'; }
        .whereinput_label::after { content:''; }
        .whereinput { flex-shrink:0; position:relative; padding:5px; border-radius:6px 6px 7px 7px; display: flex; flex-wrap: nowrap; padding: 0; align-items: stretch; }
        .whereinput { background-color:unset !important; border-width:3px; border-style:solid; }
        .whereinput select { border: unset; font-size:0.9em; }
        #goto , #page , #from { width:100%; border-radius: 0; border: unset; border-left: 1px solid lightgray; border-right: 1px solid lightgray; }
        .where_clear { border: unset; background-color: transparent; padding: 3px 9px; }


        .infocontainer { display:flex; flex-wrap: wrap; gap:5px; margin:5px 0;}
        .infobox { border:1px solid lightgray; padding:5px; border-radius:5px; }
        
        .switch { position: relative; display: inline-block; width: 24px; height: 18px; }
		.switch input { position:absolute; opacity: 0; width: 0; height: 0; }
		.slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: red; -webkit-transition: .4s; transition: .4s; border-radius: 30px;  }
		.slider:before { position: absolute; content: ""; height: 12px; width: 12px; left: 3px; bottom: 3px; background-color: white; -webkit-transition: .4s; transition: .4s; border-radius: 50%; }
		input:checked + .slider { background-color: green; }
		input:focus + .slider { box-shadow: 0 0 1px green; }
		input:checked + .slider:before { -webkit-transform: translateX(6px); -ms-transform: translateX(6px); transform: translateX(6px); }
		
		.preset { position: relative; height: 18px; }
		.preset input { position:absolute; opacity: 0; width: 0; height: 0; }
		.preset_bg { outline:1px solid lightgray; position: relative; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: transparent; border-radius: 5px; padding:5px;   }
		input:checked + .preset_bg { outline:2px solid Burlywood; }
		
				
        #stats { transition:0.3s ease-out; margin:0 -10px; }
        
        #loading { margin:10px 0 20px 0; font-size:250%; }
        .loadingstats { filter: blur(0.5px) grayscale(0.5); opacity:0.8; }
        
        #foundnothing { position: absolute; font-size: 30px; top: 32%; left: 25%; right: 25%; display: flex; align-items: center; justify-content: center; color: silver; z-index: 1; text-shadow: 1px 1px 1px #999; box-shadow: 1px 1px 3px #ccc; background-color: white; border: 1px solid #ddd; padding: 18px 18px 22px 18px; width: 50%; border-radius: 10px; }
        
        .filterslegend { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }
        .filtertag { padding: 5px 7px; border-radius: 5px; }
        .filtertag::first-letter { text-transform: capitalize; }
		.superscript { font-size:75%; position:relative; top:-5px; }
        
        .statslegend { display:flex; gap:5px; justify-content:flex-end; margin-bottom:15px; }
        .statslegend .total { padding:5px; border-radius:5px; flex-basis: 70px; flex-grow: 1; text-align: center; }
        .statslegend .total .totalcounter { color:black; background-color:white; display:flex; flex-direction:column; padding:5px; border-radius:5px; font-size:14px; font-weight:600; margin-top:5px; text-align:center; }
        .multimultimultibar { display: flex; flex-wrap: wrap; gap: 5px; }
        .multimultibar { border-radius: 5px; padding: 5px; display: flex; flex-direction: column; width: 300px; flex-grow: 1; max-width: calc(100% - 10px); }
        .multimultibar h2 { padding: 5px 5px 3px 5px; font-weight:600; font-size:150%; margin: 10px 5px; }
        .multimultibar .totalcounter { background-color: white; display: block; padding: 5px; border-radius: 5px; font-size: 14px; font-weight: 600; margin-top: 5px; }
        .multimultibarcounters { display:flex; gap:5px; margin-bottom:5px;}
        .multibars { padding:5px; background-color:white; border-radius:5px; max-height: 16.7em; min-height: 16.7em; overflow: hidden; }
        .multibarslarge { max-height: 33.4em; }        
        .multibarsopen { max-height: unset; }
        .showall { background-color: transparent; border: none; padding: 5px 0; color: white; font-weight: 600; }
    
        .multibar { position: relative; border-bottom:none; display: flex; height: 1.7em; overflow: hidden; background-color:White !important; }
        .multibar * { position:relative; z-index:3; color:black; }
        .multibar .bartext { text-overflow: ellipsis; white-space: nowrap; overflow: hidden; }
        .multibar span { padding:0 5px; }
        .multibarbar { height: calc(100% - 4px); border-radius:5px; margin: 2px; filter: brightness(1.6); position: absolute; z-index:1; }
        
        #chart { position:relative; height:360px; width:calc(100% - 15px); margin: 25px 0 15px 15px; }
        
            #x-axis { position:absolute; padding: 0 0 0 15px; width:calc(100% - 30px - 2vw); display: flex;justify-content: space-between;flex-direction: row-reverse; }
            .x-unit { width:0; display:flex; align-items:flex-end; justify-content: center; height:350px; text-align:center;  }
            .x-unit span { background-color: white; margin-bottom: -2px; z-index: 2; }
            .x-unit.past { border-right:1px solid #e5e5e5; }
            .x-unit.future { filter: brightness(1.2); border-right: 1px dashed #b6b6b6; }
            #y-axis { position:absolute; top:-10px; left:-10px; padding: 10px 0; display:flex; flex-direction:column; justify-content:space-between; height: 300px; margin-top:10px; font-size: min( 100% , calc(1vw + 1vh)); width: 100%; overflow: hidden; }
            .y-unit { height:0; display:flex; flex-direction:column; align-items:flex-start; justify-content:center; width:100vw; border-bottom:1px solid #eeeeee; }
            .y-unit span { background-color: white; padding: 0 7px 0 0; }
            .y-unit:last-child { border-color:gray; }
            
            #polylines { position:absolute; width:calc(100% - 30px - 2vw); margin:0 0 0 15px; height:300px; padding-top:10px; }
            #polylines svg { position:absolute;display:flex;width:100%;height:300px;transform: scaley(-1); }
            #polylines polyline , #polylines path { fill:none;  stroke-width:1.5; }
            
            #counters , #bars { position:absolute; margin: 0 0 0 15px;width: calc(100% - 30px - 2vw);  height:300px; margin-top:10px; }
            .counter { transform: scale(0.62) translateX(6px) translateY(6px); transition:0.2s; text-align:center; font-size:8px; position:absolute; height: 12px; width: 12px;  border-radius: 50%; transform-origin: 12px 12px; display:flex; align-items:center; justify-content:center; color:transparent !important; }
            .counter:hover { transform: scale(1.62) translateX(6px) translateY(6px); background-color:white; color:black !important; font-weight:600; outline-width:1px; }
            .barcounter { transform: translateX(6px); transition: 0.2s; position: absolute; width: 12px; transform-origin: 12px 12px; bottom:0; }
    
    
            .users          { color:black; background-color: Coral;             border-color: Coral;            outline:0px solid Coral;            stroke:Coral; }
            .time           { color:black; background-color: Burlywood;         border-color: Burlywood;        outline:0px solid Burlywood;        stroke:Burlywood; }
            .session        { color:black; background-color: MediumSeaGreen;    border-color: MediumSeaGreen;   outline:0px solid MediumSeaGreen;   stroke:MediumSeaGreen; }
            .referrers      { color:black; background-color: CornflowerBlue;    border-color: CornflowerBlue;   outline:0px solid CornflowerBlue;   stroke:CornflowerBlue; }
            .externalclicks { color:black; background-color: Orange;            border-color: Orange;           outline:0px solid Orange;           stroke:Orange; }

    </style>
    
    <main>
        
        <h1>Stats</h1>

        <section class="inputs" >
            
            <div class="inputs" >
                
                <div class="who" id="who" class="textinput inputmargin" >
                    <?php   $who_tags = array(  'Log Status'    =>  array(  'loggedin'      => array('',        'Logged in'),
                                                                            'loggedout'     => array('checked', 'Logged out'),
                                                                            'selected'      => 'selected',
                                                                    ),
                                                'Device'        =>  array(  'desktop'       => array('checked', 'Desktop'),
                                                                            'tablet'        => array('checked', 'Tablet'),
                                                                            'mobile'        => array('checked', 'Mobile'),
                                                                            'otherdevice'   => array('checked', 'Other'),
                                                                            'selected'      => ''
                                                                    ),
                                                'OS'            =>  array(  'apple'         => array('checked', 'Apple'),
                                                                            'win'           => array('checked', 'Windows'),
                                                                            'unix'          => array('checked', 'Linux'),
                                                                            'otheros'       => array('checked', 'Other'),
                                                                            'selected'      => ''
                                                                    )
                            );
                            $hidden_trigger = array( "selected" => "", "" => "hidden" );
                            $tab_labels = '';
                            $panels = '';
                            foreach(array_keys($who_tags) as $who_tags_key) {
                                $tab_labels .= '<span id="' . substr(strtolower($who_tags_key),0,3) . 'tab" class="who_tags_label ' . $who_tags[$who_tags_key]['selected'] . '" >' . $who_tags_key . ':</span>';
                                $panels .= '<div id="' . substr(strtolower($who_tags_key),0,3) . '_panel" class="who_tags ' . $hidden_trigger[$who_tags[$who_tags_key]['selected']] . ' filterpanel" >';
                                foreach(array_keys($who_tags[$who_tags_key]) as $who_tag_key) {
                                    if($who_tag_key == 'selected') continue;
                                    $panels .= '<label class="who_tag" for="' . $who_tag_key . '">
                                            <label class="switch" for="' . $who_tag_key . '" >
                                                <input id="' . $who_tag_key . '" type="checkbox" ' . $who_tags[$who_tags_key][$who_tag_key][0] . ' >
                                                <span class="slider"></span>
                                            </label>
                                            ' . $who_tags[$who_tags_key][$who_tag_key][1] . '
                                          </label>';
                                }
                                $panels .= '</div>';
                            }
                            echo '<div class="who_tab" >' . $tab_labels . '</div>';
                            echo $panels;
                    ?>
                </div>
    
                <div class="when" id="when" >
                    <span id="mode" style="display:none;">daily</span>
                    <?php date_default_timezone_set(wp_timezone_string()); ?>
                    <?php   $when_modes =   array(  'Hourly'    =>  array(  'custom'        => array( 'hourly', 'hour', 'datetime-local', date('Y-m-d\TH:\0\0', time()) ),
                                                                            'selected'      => ''
                                                                    ),
                                                    'Daily'     =>  array(  'custom'        => array( 'daily', 'day', 'date', date('Y-m-d', time()) ),
                                                                            'selected'      => 'selected'
                                                                    ),
                                                    'Weekly'    =>  array(  'custom'        => array( 'weekly', 'week', 'week', date('Y-\WW', time()) ),
                                                                            'selected'      => ''
                                                                    ),
                                                    'Monthly'   =>  array(  'custom'        => array( 'monthly', 'month', 'month', date('Y-m', time()) ),
                                                                            'selected'      => ''
                                                                    ),
                                                    'Yearly'    =>  array(  'custom'        => array( 'yearly', 'year', 'number', date('Y', time()) ),
                                                                            'selected'      => ''
                                                                    )
                            );
                            $hidden_trigger = array( "selected" => "", "" => "hidden" );
                            $tab_labels = '';
                            $panels = '';
                            foreach(array_keys($when_modes) as $when_modes_key) {
                                $when_mode_keys = array_keys($when_modes[$when_modes_key]);
                                $tab_labels .= '<span id="' . $when_modes[$when_modes_key]['custom'][0] . 'tab" class="whenmode_label ' . $when_modes[$when_modes_key]['selected'] . '" >' . $when_modes_key . '</span>';
                                $panels .= '<div id="' . $when_modes[$when_modes_key]['custom'][0] . '_panel" class="whenmode ' . $hidden_trigger[$when_modes[$when_modes_key]['selected']] . ' filterpanel" >
                                                <div class="" >
                                                    <label class="" for="' . $when_modes[$when_modes_key]['custom'][0] . '">This ' . $when_modes[$when_modes_key]['custom'][1] . ':</label>
                                                    <input  type="' . $when_modes[$when_modes_key]['custom'][2] . '"
                                                            id="' . $when_modes[$when_modes_key]['custom'][0] . '"
                                                            value="' . $when_modes[$when_modes_key]['custom'][3] . '"
                                                    />
                                                </div>
                                            </div>';
                            }
                            echo '<div class="when_tab" >' . $tab_labels . '</div>';
                            echo $panels;
                    ?>
                </div>
    
                
                <div id="where" class="where" >
                    <?php   $where_input = array(   'page'  =>  array(  'class' => 'session',            'show' => '',        'label' => 'Page',        'selected' => 'selected' ),
                                                    'from'  =>  array(  'class' => 'referrers',          'show' => 'hidden',  'label' => 'Referrer',    'selected' => '' ),
                                                    'goto'  =>  array(  'class' => 'externalclicks',     'show' => 'hidden',  'label' => 'Click',       'selected' => '' )
                            );
                            $tab_labels = '';
                            $panels = '';
                            foreach(array_keys($where_input) as $where_input_key) {
                                $tab_labels .= '<span id="' . $where_input_key . 'tab" class="whereinput_label ' . $where_input[$where_input_key]['class'] . ' ' . $where_input[$where_input_key]['selected'] . '" for="' . $where_input_key . '">' . $where_input[$where_input_key]['label'] . '</span>';
                                $panels .= '<div id="' . $where_input_key . 'panel" class="' . $where_input[$where_input_key]['class'] . ' ' . $where_input[$where_input_key]['show'] . ' whereinput filterpanel" >
                                            
                                            <select id="' . $where_input_key . 'mode" >
                                                <option value="islike" >Contains:</option>
                                                <option value="notlike" >Not contains:</option>
                                                <option value="isequal" >Exactly this:</option>
                                                <option value="notequal" >Exclude this:</option>
                                            </select>
                                            <input id="' . $where_input_key . '" type="text" placeholder="e.g. https://www..." >
                                            <input id="' . $where_input_key . 'clear" class="where_clear" value="Clear" type="submit" >
                                          </div>
                                ';
                            }
                            echo '<div class="where_tab" >' . $tab_labels . '</div>';
                            echo $panels;
                    ?>
                </div>
                
            </div>
            
            <p>You are viewing data for: <span id="filters_display" ></span></p>
        
        </section>
        
        <section>
            
            <span id="loading" >LOADING...</span>
        
            <?php   $metrics = array(   'users'             => array( 'legend' => true, 'label' => 'Users' ),
                                        'session'           => array( 'legend' => true, 'label' => 'Visits' ),
                                        'externalclicks'    => array( 'legend' => true, 'label' => 'Clicks' ),
                                        'referrers'         => array( 'legend' => false, 'label' => 'Referrers' )
                    );
            ?>
    
            <div class="statslegend" >
                <?php   foreach(array_keys($metrics) as $metric_key) {
                            if( $metrics[$metric_key]['legend'] ) {
                                echo '<div class="total ' . $metric_key . '" >' . $metrics[$metric_key]['label'] . '<br>
                                        <span class="totalcounter ' . $metric_key . '" >?</span>
                                      </div>';
                            }
                        }
                ?>
            </div>

            <div id="stats" ></div>
            
            <div class="multimultimultibar" >
                <?php   $multibar_layout = array(   'counters' => array(    'total' => 'Total', 'avg' => 'Avg', 'min' => 'Min', 'max' => 'Max' ),
                        );
                
                        foreach(array_keys($metrics) as $metric_key) {
                            echo '<div class="multimultibar ' . $metric_key . '" >';
                                echo '<h2>' . $metrics[$metric_key]['label'] . '</h2>';
                                echo '<div class="multimultibarcounters" >';
                                    foreach(array_keys($multibar_layout['counters']) as $counter_key) {
                                        echo '<div class="totalcounter ' . $counter_key . '" >' . $multibar_layout['counters'][$counter_key]  . ': <span class="metric' . $counter_key . '" >?</span></div>';
                                    }
                                echo '</div>';
                                echo '<div class="multibars" >';
                                echo '<div class="multibar  ' . $metric_key . '" >
                                            <div class="multibarbar ' . $metric_key . '" style="width:calc(100% - 4px);"></div>
                                                <span class="" data-url="" >[?]</span>
                                            <span class="bartext" >???</span>
                                            <b><a href="" target="_blank" >[➚]</a></b>
                                     </div>';
                                echo '</div>'; 
                                echo '<button class="showall" data-class="' . $metric_key . '" >+ Show more +</button>';
                            echo '</div>';
                        }
                ?>
            </div>
            
        </section>

        <script>
            var myAjax = {"ajaxurl":"<?php echo get_site_url(); ?>/wp-admin/admin-ajax.php"};
            whofilter = jQuery("#who");
            whenfilter = jQuery("#when");
            wherefilter = jQuery("#where");
            
            function stats_query(e) {
                filtersdisplay();
                jQuery("#loading").css("display", "block");
                jQuery("#stats").addClass("loadingstats");
                var mode = jQuery("#mode").text();
                var action = "bubo_insights_stats_query";
                var adminpage = "<?php echo $_GET["page"]; ?>";
                var who = '';
                who = {
                    loggedin: jQuery("#loggedin")[0].checked,
                    loggedout: jQuery("#loggedout")[0].checked,
                    desktop: jQuery("#desktop")[0].checked,
                    tablet: jQuery("#tablet")[0].checked,
                    mobile: jQuery("#mobile")[0].checked,
                    otherdevice: jQuery("#otherdevice")[0].checked,
                    apple: jQuery("#apple")[0].checked,
                    win: jQuery("#win")[0].checked,
                    unix: jQuery("#unix")[0].checked,
                    otheros: jQuery("#otheros")[0].checked
                };
                var when = '';
                when = {
                    mode:   mode,
                    hour:   jQuery("#hourly").val(),
                    day:    jQuery("#daily").val(),
                    week:   jQuery("#weekly").val(),
                    month:  jQuery("#monthly").val(),
                    year:   jQuery("#yearly").val(),
                };
                var where = '';
                where = {
                    gotomode:   jQuery("#gotomode").val(),
                    goto:       jQuery("#goto").val(),
                    pagemode:   jQuery("#pagemode").val(),
                    page:       jQuery("#page").val(),
                    frommode:   jQuery("#frommode").val(),
                    from:       jQuery("#from").val()
                }
                jQuery.ajax( myAjax.ajaxurl, {
                    method : "POST",
                    dataType : "json",
                    data : {action: action, who: who, when: when, where: where, adminpage: adminpage },
                    success: function(response) {
                        jQuery("#stats").removeClass("loadingstats");
                        jQuery("#stats").html(response.chart);
                        Object.entries(response.legend).forEach( ([key, value]) => {
                            jQuery(".totalcounter."+key).html(value);
                        });
                        Object.entries(response.multibarcounters).forEach( ([key, value]) => {
                            Object.entries(value).forEach( ([subkey, subvalue]) => {
                                jQuery(".multimultibar."+key+" .metric"+subkey).html(subvalue);
                            });
                        });
                        Object.entries(response.multibars).forEach( ([key, value]) => {
                            jQuery(".multimultibar."+key+" .multibars").html(value);
                        });
                        jQuery("#loading").css("display", "none");
                        console.log("Stats loaded!");
                    },
                    error: function(response) {
                        console.log("Problem loading stats");				 
                    }
                });
            }
            
            function filtersdisplay() {
                const who_filters_legend = {    'loggedin' : 'logged in',
                                                'loggedout' : 'logged out',
                                                'desktop' : 'desktop',
                                                'tablet' : 'tablet',
                                                'mobile' : 'mobile',
                                                'otherdevice' : '"uncategorized" devices',
                                                'apple' : 'Apple OS',
                                                'win' : 'Windows OS',
                                                'unix' : 'UNIX/Linux OS',
                                                'otheros' : '"uncategorized" OS'
                };
                var who_filters = '<span class="filtertag users" >All users</span>';
                Object.entries(who_filters_legend).forEach( ([key, value]) => {
                    if(!jQuery("#"+key)[0].checked) who_filters += '<span class="filtertag users" >excluding '+value+' users</span>';
                });

                function nth(n) {
                    return["st","nd","rd"][((n+90)%100-10)%10-1]||"th"
                }
                const when_months = {   '01' :'January',
                                        '02' :'February',
                                        '03' :'March',
                                        '04' :'April',
                                        '05' :'May',
                                        '06' :'June',
                                        '07' :'July',
                                        '08' :'August',
                                        '09' :'September',
                                        '10' :'October',
                                        '11' :'November',
                                        '12' :'December'
                }
                const when_filters_legend = {   'hourly' : { 'value' : jQuery("#hourly").val().substring(11,13)+':00 - '+jQuery("#hourly").val().substring(11,13)+':59 of '+parseInt(jQuery("#hourly").val().substring(8,10))+' '+when_months[jQuery("#hourly").val().substring(5,7)].substring(0,3)+' '+jQuery("#hourly").val().substring(0,4) },
                                                'daily' : { 'value' : parseInt(jQuery("#daily").val().substring(8,10))+' '+when_months[jQuery("#daily").val().substring(5,7)].substring(0,3)+' '+jQuery("#daily").val().substring(0,4) },
                                                'weekly' : { 'value' : parseInt(jQuery("#weekly").val().substring(6,8))+'<span class="superscript" >'+nth(parseInt(jQuery("#weekly").val().substring(6,8)))+'</span> week of '+jQuery("#weekly").val().substring(0,4) },
                                                'monthly' : { 'value' : when_months[jQuery("#monthly").val().substring(5,7)]+' '+jQuery("#monthly").val().substring(0,4) },
                                                'yearly' : { 'value' : 'year '+jQuery("#yearly").val() }
                }
                var when_filters = '<span class="filtertag time" >';
                Object.entries(when_filters_legend).forEach( ([key, value]) => {
                    if(jQuery("#mode").text()==key) when_filters += value.value;
                });
                when_filters += '</span>';
                var where_goto_filters = '';
                    if(jQuery("#goto").val()) where_goto_filters += '<span class="filtertag externalclicks" >Pages leading to URLs that '+jQuery("#gotomode option[value="+jQuery("#gotomode").val()+"]").html();
                    if(jQuery("#goto").val()) where_goto_filters += ' "'+jQuery("#goto").val()+'"</span>';
                var where_page_filters = '';
                    if(jQuery("#page").val()) where_page_filters += '<span class="filtertag session" >Pages with URLs that '+jQuery("#pagemode option[value="+jQuery("#pagemode").val()+"]").html();
                    if(jQuery("#page").val()) where_page_filters += ' "'+jQuery("#page").val()+'"</span>';
                var where_from_filters = '';
                    if(jQuery("#from").val()) where_from_filters += '<span class="filtertag referrers" >Pages referred by URLs that '+ jQuery("#frommode option[value="+jQuery("#frommode").val()+"]").html();
                    if(jQuery("#from").val()) where_from_filters += ' "'+jQuery("#from").val()+'"</span>';
                var where_filters = where_page_filters+where_from_filters+where_goto_filters;
                jQuery("#filters_display").html('<div class="filterslegend" >'+who_filters+' '+when_filters+' '+where_filters+'</div>');
            }
            jQuery(document).ready( function() {
                
                stats_query('');
                jQuery("#who").on('change', 'input', function(e) { stats_query() } );
	            jQuery("#when").on('click change keyup paste', 'input', function(e) { stats_query() } );
	            
                jQuery("#where").on('change', 'select', function(e) { stats_query() } );
                jQuery("#where").on('change keyup paste', 'input', function(e) { stats_query() } );
                
                const whotags = [ 'log', 'dev', 'os' ];
                Object.entries(whotags).forEach( ([key, value]) => {
                    jQuery("#"+value+"tab").on('click' , function(e) { 
                        jQuery(".who_tags_label").removeClass("selected");
                        jQuery("#"+value+"tab").addClass('selected');
                        jQuery(".who_tags").addClass("hidden");
                        jQuery("#"+value+"_panel").removeClass("hidden");
                    } );
                });
                
                const whenmodes = [ 'hourly', 'daily', 'weekly', 'monthly', 'yearly' ];
                Object.entries(whenmodes).forEach( ([key, value]) => {
                    jQuery("#"+value+"tab").on('click' , function(e) { 
                        jQuery(".whenmode_label").removeClass("selected");
                        jQuery("#"+value+"tab").addClass('selected');
                        jQuery(".whenmode").addClass("hidden");
                        jQuery("#"+value+"_panel").removeClass("hidden");
                        jQuery("#mode").html(value);
                        stats_query();
                    } );
                });
                
                const whereinput = [ 'page', 'from', 'goto' ];
                Object.entries(whereinput).forEach( ([key, value]) => {
                    jQuery("#"+value+"tab").on('click' , function(e) {
                        jQuery(".whereinput_label").removeClass("selected");
                        jQuery("#"+value+"tab").addClass('selected');
                        jQuery(".whereinput").addClass('hidden');
                        jQuery("#"+value+"panel").removeClass('hidden');
                    } );
                    jQuery("#"+value+"clear").on('click' , function(e) {
                        jQuery("#"+value).val('');
                        stats_query();
                    } );
                });
                
                jQuery("body").on('click' , '.pagex', function(e) { jQuery("#page").val(e.target.dataset.url); stats_query(); } );
                jQuery("body").on('click' , '.gotox', function(e) { jQuery("#goto").val(e.target.dataset.url); stats_query(); } );
                jQuery("body").on('click' , '.fromx', function(e) { jQuery("#from").val(e.target.dataset.url); stats_query(); } );
                
                jQuery(".total").on('click', function(e) {
                    var targetClass = e.currentTarget.classList[1];
                    jQuery(".total."+targetClass).toggleClass("disabled");
                    jQuery("path."+targetClass).toggle();
                    jQuery("#counters ."+targetClass).toggle();
                    jQuery("#bars ."+targetClass).toggle();
                } );
                
                jQuery("body").on('click', '.showall', function(e) { 
                    let eclass = e.target.getAttribute("data-class");
                    if(jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML == "+ Show more +") {
                        jQuery(".multimultibar."+eclass+" .multibars").addClass("multibarslarge");
                        jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML = "+ Show all +";
                    }
                    else if(jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML == "+ Show all +") {
                        jQuery(".multimultibar."+eclass+" .multibars").addClass("multibarsopen");
                        jQuery(".multimultibar."+eclass+" .multibars").removeClass("multibarslarge");
                        jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML = "- Show less -";
                    }
                    else if(jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML == "- Show less -") {
                        jQuery(".multimultibar."+eclass+" .multibars").removeClass("multibarsopen");
                        jQuery(".multimultibar."+eclass+" .showall")[0].innerHTML = "+ Show more +";
                    }
                } );
            });
        </script>
        
    </main>
    
    <?php

}

/* stats page AJAX */
add_action('wp_ajax_bubo_insights_stats_query', 'bubo_insights_stats_query_callback');

function bubo_insights_stats_query_callback() {
  
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		
		$adminpage = $_REQUEST['adminpage'];
		$who = $_REQUEST['who'];
        $whenx = $_REQUEST['when'];
		$where = $_REQUEST['where'];
		
		if($whenx['mode'] == 'hourly'){
		    $target_year = substr($whenx['hour'], 0, 4);
            $target_month = substr($whenx['hour'], 5, 2);
            $target_week = substr($whenx['week'], -2, 2);
            $target_day = substr($whenx['hour'], -8, 2);
            $target_hour = substr($whenx['hour'], -5, 2);
		}		
		if($whenx['mode'] == 'daily'){
		    $target_year = substr($whenx['day'], 0, 4);
            $target_month = substr($whenx['day'], -5, 2);
            $target_week = substr($whenx['week'], -2, 2);
            $target_day = substr($whenx['day'], -2);
            $target_hour = substr($whenx['hour'], -5, 2);
		}
		if($whenx['mode'] == 'monthly'){
		    $target_year = substr($whenx['month'], 0, 4);
            $target_month = substr($whenx['month'], -2, 2);
            $target_week = substr($whenx['week'], -2, 2);
            $target_day = substr($whenx['day'], -2);
            $target_hour = substr($whenx['hour'], -5, 2);
		}
		if($whenx['mode'] == 'weekly'){
		    $target_year = substr($whenx['month'], 0, 4);
            $target_month = substr($whenx['month'], -2, 2);
            $target_week = substr($whenx['week'], -2, 2);
            $target_day = substr($whenx['day'], -2);
            $target_hour = substr($whenx['hour'], -5, 2);
		}
		if($whenx['mode'] == 'yearly'){
		    $target_year = $whenx['year'];
            $target_month = substr($whenx['month'], -2, 2);
            $target_week = substr($whenx['week'], -2, 2);
            $target_day = substr($whenx['day'], -2);
            $target_hour = substr($whenx['hour'], -5, 2);
		}
		
	    $when = array(
	        'mode' => $whenx['mode'],
	        'hour' => $target_hour,
	        'day' => $target_day,
	        'week' => $target_week,
	        'month' => $target_month,
	        'year' => $target_year
	    );
        
        $block = bubo_insights_stats_page_dynamic_contents($who, $when, $where);
		
		$response = json_encode($block);
		
      	echo $response;
   	}
   	else {   
      	header("Location: ".$_SERVER["HTTP_REFERER"]); 
   	}
  
  	die();
}
/* ajax */

function bubo_insights_stats_page_dynamic_contents($who, $when, $where) {

    $who_query = " AND ( (false) "; 
    if($who['loggedin'] == 'true' ) $who_query .= "OR loggedin = 1 "; 
    if($who['loggedout'] == 'true' ) $who_query .= "OR loggedin != 1 OR loggedin IS NULL ";
    $who_query .= " ) ";
    $who_query .= " AND ( (false) "; 
    if($who['desktop'] == 'true' ) $who_query .= "OR device = 'd' ";
    if($who['tablet'] == 'true' ) $who_query .= "OR device = 't' ";
    if($who['mobile'] == 'true' ) $who_query .= "OR device = 'm' ";
    if($who['otherdevice'] == 'true' ) $who_query .= "OR device = '?' OR device IS NULL ";
    $who_query .= " ) ";
    $who_query .= " AND ( (false) ";
    if($who['apple'] == 'true' ) $who_query .= "OR os = 'a' ";
    if($who['win'] == 'true' ) $who_query .= "OR os = 'w' ";
    if($who['unix'] == 'true' ) $who_query .= "OR os = 'u' ";
    if($who['otheros'] == 'true' ) $who_query .= "OR os = '?' OR os IS NULL "; 
    $who_query .= " ) ";
    $where_query = " AND ( (true) ";
    if($where['goto'] != '' ){
        if($where['gotomode'] == 'islike' ) $where_query .= " AND link LIKE '%" . $where['goto'] . "%' ";
        if($where['gotomode'] == 'notlike' ) $where_query .= " AND link NOT LIKE '%" . $where['goto'] . "%' ";
        if($where['gotomode'] == 'isequal' ) $where_query .= " AND link = '" . $where['goto'] . "' ";
        if($where['gotomode'] == 'notequal' ) $where_query .= " AND link <> '" . $where['goto'] . "' ";
    }
    if($where['page'] != '' ){
        if($where['pagemode'] == 'islike' ) $where_query .= " AND origin LIKE '%" . $where['page'] . "%' ";
        if($where['pagemode'] == 'notlike' ) $where_query .= " AND origin NOT LIKE '%" . $where['page'] . "%' ";
        if($where['pagemode'] == 'isequal' ) $where_query .= " AND origin = '" . $where['page'] . "' ";
        if($where['pagemode'] == 'notequal' ) $where_query .= " AND origin <> '" . $where['page'] . "' ";
    }
    if($where['from'] != '' ){    
        if($where['frommode'] == 'islike' ) $where_query .= " AND referrer LIKE '%" . $where['from'] . "%' ";
        if($where['frommode'] == 'notlike' ) $where_query .= " AND referrer NOT LIKE '%" . $where['from'] . "%' ";
        if($where['frommode'] == 'isequal' ) $where_query .= " AND referrer = '" . $where['from'] . "' ";
        if($where['frommode'] == 'notequal' ) $where_query .= " AND referrer <> '" . $where['from'] . "' ";
    }
    $where_query .= " ) ";

    $timezone_correction = time() - current_time('U');
    $time = strtotime('tomorrow, midnight') + $timezone_correction;
    
    $target_year = $when['year'];
    $target_month = substr($when['month'], -2);
    $target_day = substr($when['day'], -2);
    
    $monthdays = array( 31, cal_days_in_month(CAL_GREGORIAN,2,date($target_year)), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );

    $timespan = $when['mode'];
    $timespans = array(
        'hourly'     => array(
            'time' => strtotime(($when['year']) . '/' . ($when['month']) . '/' . ($when['day']) . ' ' . ($when['hour'])  . ':00 ') + 3600 + $timezone_correction,
            'limit' => 12,
            'slottime' => array_fill(0, 12, 300),
            'display_correction' => -1,
            'display_multiplier' => 5,
            'display_tags'  => array( '0′', '5′', '10′', '15′', '20′', '25′', '30′', '35′', '40′', '45′', '50′', '55′' )
        ),
        'daily'     => array(
            'time' => strtotime(($when['year']) . '/' . ($when['month']) . '/' . ($when['day']) . ' midnight') + 86400 + $timezone_correction,
            'limit' => 24,
            'slottime' => array_fill(0, 24, 3600),
            'display_correction' => 0,
            'display_multiplier' => 1,
            'display_tags'  => array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23' )
        ),
        'weekly'     => array(
            'time' => strtotime( ($when['year']) . 'W' . ($when['week']) ) + 604800 + $timezone_correction,
            'limit' => 7,
            'slottime' => array_fill(0, 7, 86400),
            'display_correction' => 0,
            'display_multiplier' => 1,
            'display_tags'  => array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' )
        ),
        'monthly'   => array(
            'time' => strtotime(($when['year']) . '/' . ($when['month']) . '/01 midnight') + ($monthdays[$when['month'] - 1] * 86400) + $timezone_correction,
            'limit' => $monthdays[$target_month - 1],
            'slottime' => array_fill(0, $monthdays[$target_month - 1], 86400),
            'display_correction' => -1,
            'display_multiplier' => 1,
            'display_tags'  => array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'  )
        ),
        'yearly'    => array(
            'time' => strtotime(($when['year'] + 1) . '/01/01 midnight') + $timezone_correction,
            'limit' => 12,
            'slottime' => array_map(function($el) { return $el * 86400; }, $monthdays),
            'display_correction' => -1,
            'display_multiplier' => 1,
            'display_tags'  => array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Set', 'Oct', 'Nov', 'Dec' )
        ),
    );
    
    $now = time();
    $time = $timespans[$when['mode']]['time'];
    $limit = $timespans[$when['mode']]['limit'];
    $bars = array();
    for( $b=0; $b<$limit; $b++ ) {
        $bars[$b]['index'] = $b;
        $bars[$b]['curve'] = 'C';
        $bars[$b]['slottime'] = $timespans[$when['mode']]['slottime'][$b];
        $bars[$b]['display'] = $timespans[$when['mode']]['display_tags'][( $limit - 1 - $b  )];
        $bar_slot_time = $timespans[$when['mode']]['slottime'][$b];
        $bars[$b]['maxtime'] = $time - ($bar_slot_time * $b);
        $bars[$b]['mintime'] = $time - $bar_slot_time - ($bar_slot_time * $b);
        $bars[$b]['is_in_the_future'] = (( $time - $bar_slot_time - ($bar_slot_time * $b) ) > $now );
    }
    $bars[$limit - 1]['curve'] = 'L';
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'bubo_insights_event_log';
    $site_host = $_SERVER['SERVER_NAME'];
    
    $metrics = array(
        'users'  => array(
            'label' => 'Users',
            'class' => 'users',
            'total' => 0,
            'avg_timespan' => 0,
            'min_timespan' => 0,
            'max_timespan' => 0,
            'bars' => array(),
            'condition' => true,
            'select_query' => 'count(DISTINCT user)',
            'what_query' => ' ',
            'from_query' => $table_name,
            'polyline' => '',
            'polyline_enabled' => true,
            'bar_enabled' => false,
            'multibar' => array(),
            'multibar_enabled' => true,
            'multibar_select_query' => 'count(DISTINCT user) as count , device as row',
            'multibar_order_query' => ' GROUP BY device ORDER BY count(DISTINCT user) DESC',
            'multibar_row_click' => '',
        ),
        'session' => array(
            'label' => 'Visits',
            'class' => 'session',
            'total' => 0,
            'avg_timespan' => 0,
            'min_timespan' => 0,
            'max_timespan' => 0,
            'bars' => array(),
            'condition' => true,
            'select_query' => 'count(DISTINCT pagesession)',
            'what_query' => ' ',
            'from_query' => $table_name,
            'polyline' => '',
            'polyline_enabled' => true,
            'bar_enabled' => false,
            'multibar' => array(),
            'multibar_enabled' => true,
            'multibar_select_query' => 'count(DISTINCT pagesession) as count , origin as row',
            'multibar_order_query' => ' GROUP BY origin ORDER BY count(DISTINCT pagesession) DESC',
            'multibar_row_click' => 'pagex',
        ),
        'referrers' => array(
            'label' => 'Referrers',
            'class' => 'referrers',
            'total' => 0,
            'avg_timespan' => 0,
            'min_timespan' => 0,
            'max_timespan' => 0,
            'bars' => array(),
            'condition' => true,
            'select_query' => 'count(DISTINCT pagesession)',
            'what_query' => ' AND referrer <> \'\' AND referrer not like \'%' . $site_host . '%\' ',
            'from_query' => $table_name,
            'polyline' => '',
            'polyline_enabled' => false,
            'bar_enabled' => false,
            'multibar' => array(),
            'multibar_enabled' => true,
            'multibar_select_query' => 'count(DISTINCT pagesession) as count , referrer as row',
            'multibar_order_query' => ' GROUP BY referrer ORDER BY count(DISTINCT pagesession) DESC',
            'multibar_row_click' => 'fromx',
        ),
        'externalclicks' => array(
            'label' => 'Clicks',
            'class' => 'externalclicks',
            'total' => 0,
            'avg_timespan' => 0,
            'min_timespan' => 0,
            'max_timespan' => 0,
            'bars' => array(),
            'condition' => true,
            'select_query' => 'count(*)',
            'what_query' => ' AND eventtype = \'click\' AND link <> \'\' AND link not like \'%' . $site_host . '%\'  ',
            'from_query' => $table_name,
            'polyline' => '',
            'polyline_enabled' => false,
            'bar_enabled' => true,
            'multibar' => array(),
            'multibar_enabled' => true,
            'multibar_select_query' => 'count(link) as count , link as row',
            'multibar_order_query' => ' GROUP BY link ORDER BY count(link) DESC',
            'multibar_row_click' => 'gotox',
        ),
    );
    
    $weekday = 0;
    foreach($bars as $bar) {
        $maxtime = $time - ($bar['slottime'] * $bar['index']);
        $mintime = $time - $bar['slottime'] - ($bar['slottime'] * $bar['index']);
        $maxtime = $bar['maxtime'];
        $mintime = $bar['mintime'];
        $when_query = ' AND eventtime >= ' . $mintime . ' AND eventtime < ' . $maxtime . ' ';
        if($when['mode'] == 'weekly') {
            $weekday_monthnumber = date( "j" , $mintime );
            $day_suffix = date( "S" , $mintime );
            $monthname = date( "M" , $mintime );
            $bars[$weekday++]['display'] .= ' <small>' . $weekday_monthnumber . '<sup>' . $day_suffix . '</sup> ' . $monthname . '</small>';
        }
        foreach($metrics as $metric) {
            if($metric['condition']) {
                $query_text = 'SELECT ' . $metric['select_query'] . ' as count FROM ' . $metric['from_query'] . ' WHERE (true) ' . $metric['what_query'] . $who_query . $where_query . $when_query;
                $query = $wpdb->get_results($query_text);
                $metrics[$metric['class']]['bars'][$bar['index']] = intval($query[0]->count);
                $metrics[$metric['class']]['total'] += intval($query[0]->count);
                if($bar['is_in_the_future'] == false) {
                    if(!empty($metrics[$metric['class']]['polyline'])) {
                        $metrics[$metric['class']]['polyline'] .= ' '                   . $limit - 0.5  - $bar['index'] . ',' . $metrics[$metric['class']]['bars'][$bar['index']];
                    }
                    $metrics[$metric['class']]['polyline'] .= ' '                       . $limit - 1    - $bar['index'] . ',' . $metrics[$metric['class']]['bars'][$bar['index']]
                                                            . ' ' . $bar['curve'] . ' ' . $limit - 1.5  - $bar['index'] . ',' . $metrics[$metric['class']]['bars'][$bar['index']] . ' ';
                }
            }   
        }
    }
    
    $recorded_bar_count = count(array_filter(array_column($bars, 'is_in_the_future')));
    foreach($metrics as $metric) {
        if($recorded_bar_count > 0) {
            $metrics[$metric['class']]['avg_timespan'] = round( ( $metrics[$metric['class']]['total'] / $recorded_bar_count ), 2 );
        }
        if( !empty($metrics[$metric['class']]['bars']) ) {
            $non_zero_bars_array = array();
            $c = 0;
            foreach($metrics[$metric['class']]['bars'] as $bar) {
                if($bar > 0) {
                    $non_zero_bars_array[$c++] = $bar;
                }
            }
            asort($non_zero_bars_array);
            $metrics[$metric['class']]['min_timespan'] = $non_zero_bars_array[0];
            $metrics[$metric['class']]['max_timespan'] = max($metrics[$metric['class']]['bars']);
        }
    }
    
    $maxtime = $time;
    $mintime = $time - $bars[$limit - 1]['slottime'] - ($bars[$limit - 1]['slottime'] * $bars[$limit - 1]['index']);
    $when_query = ' AND eventtime >= ' . $mintime . ' AND eventtime < ' . $maxtime . ' ';
    foreach($metrics as $metric) {
        if($metric['condition']) {
            $query_text = 'SELECT ' . $metric['multibar_select_query'] . ' FROM '. $table_name . ' WHERE (true) ' . $metric['what_query'] . $who_query . $where_query . $when_query . $metric['multibar_order_query'] ;
            $query = $wpdb->get_results($query_text);
            $metrics[$metric['class']]['multibar'] = $query;
        } 
    }

ob_start();

    $maxes = array();
    foreach($metrics as $metric) {
        if(!empty($metric['bars'])) $maxes[$metric['class']] = max($metric['bars']);
    }
    $max = 1;
    if(!empty($maxes)) $max = max($maxes) * 1.1;
    
    $max_magnitude = strlen(abs(round($max,0)));
    $rounder = 10**($max_magnitude - 2);
    $max = ceil( $max / (10 * $rounder) ) * 10 * $rounder;

    if($max == 0) $max = 1;
    
    $filters_labels = array(
        'who_tags' => array( 'd' => 'Desktop', 't' => 'Tablet', 'm' => 'Mobile', '?' => 'Other devices', 'a' => 'Apple', 'w' => 'Windows', 'u' => 'Unix', '?' => 'Other OSs'),
        'when' => array( 'hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly' ),
    );
    
    
    
$block = array();

$legend = array();
foreach($metrics as $metric) {
    $legend[$metric['class']] = $metric['total'];
}
$block['legend'] = $legend;

$multibarcounters = array();
foreach($metrics as $metric) {
    $multibarcounters[$metric['class']] = array( 'total' => $metric['total'], 'avg' => $metric['avg_timespan'], 'min' => $metric['min_timespan'], 'max' => $metric['max_timespan'] );
}
$block['multibarcounters'] = $multibarcounters;

$multibars = array();
foreach($metrics as $metric) {
    $multibars[$metric['class']] = '';
                        $multimax = $metric['multibar'][0]->count;
                        if($multimax == 0) $multimax = 1;
                        for($m=0;$m<count($metric['multibar']);$m++){
                        $full_url = str_replace(array("https://", "www."), "", $metric['multibar'][$m]->row);
                        if($metric['class']=='users') {
                            $full_url = $filters_labels['who_tags'][$metric['multibar'][$m]->row];
                        }
                        $queryless_url = explode( "?", $metric['multibar'][$m]->row);
                        $url = explode( "#", $full_url);
                        if(!empty($url[1])) $url[1] = '<b>#' . $url[1] . '</b>';
                        $multibars[$metric['class']] .= '<div class="multibar  ' . $metric['class'] . '" >
                                <div class="multibarbar ' . $metric['class'] . '" style="width:calc(' . ( $metric['multibar'][$m]->count / $multimax ) * 100 . '% - 4px);"></div>
                                    <span class="' . $metric['multibar_row_click']  . '" data-url="' . $queryless_url[0] . '" >[' . $metric['multibar'][$m]->count . ']</span>
                                <span class="bartext" >' . $url[0] . $url[1] . '</span>
                                <b><a href="' . $queryless_url[0] . '" target="_blank" >[➚]</a></b>
                              </div>';
                        }
}
$block['multibars'] = $multibars;

    echo '<div id="chart" >';
    
        $no_results = array();
        foreach($metrics as $metric) {
            $no_results[$metric['label']] = $metric['total'];
        }
        if(array_sum($no_results) == 0) echo '<div id="foundnothing" >No results with these criteria.</div>';
    
        echo '<div id="x-axis" >';
        foreach($bars as $bar) {
            if($bar['is_in_the_future'] == false) {
               echo    '<div class="x-unit past" ><span>' . $bar['display'] . '</span></div>'; 
            }
            else {
                echo    '<div class="x-unit future" ><span>' . $bar['display'] . '</span></div>';
            }
        }
        echo '</div>';
        
        echo '<div id="y-axis" >';
            $y_magnitude = strlen(abs($max));
            $y_rounder = 10**($y_magnitude-1);
            $y_axis_number = ceil($max/($y_rounder));
            $y_module = ceil((2 * ($max / $y_axis_number)/($y_rounder))) * 0.5 * $y_rounder;
            if($max > 10 AND $y_axis_number < 6 ) {
                $y_axis_number = $y_axis_number * 2;
                $y_module = $y_module / 2;
            }
            for($i=0;$i<$y_axis_number+1;$i++){
                echo '<div class="y-unit" ><span>' . ($max - $i * $y_module) . '</span></div>';
            }
        echo '</div>';
        
        echo '<div id="polylines" >';
            $chart_width = $limit - 1;
            foreach($metrics as $metric) {
                if($metric['condition'] AND $metric['polyline_enabled']) {
                    echo   '<svg width="' . $chart_width . '" height="' . $max . '"
                                 viewbox="0 0 ' . $chart_width . ' ' . $max . '" preserveAspectRatio="none" >
                                <path class="' . $metric['class']  . '" d="M '. $metric['polyline'] . '" vector-effect="non-scaling-stroke" />
                            </svg>';
                }
            }
        echo '</div>';
    
        echo '<div id="counters" >';
            $column_width = round(100 / ($limit - 1) , 3);
            $column_modular_height = round(100 / $max , 4);
            foreach($metrics as $metric) { 
                $current_column = 0;
                foreach($bars as $bar) {
                    $column_right = $current_column * $column_width;
                    if($metric['condition'] AND $metric['polyline_enabled'] AND $bar['is_in_the_future'] == false) {
                        echo '<div  class="counter ' . $metric['class']  . '"
                                    style="bottom:' . ($metric['bars'][$bar['index']] * $column_modular_height) . '%;right:' . $column_right . '%;"
                                    >' . $metric['bars'][$bar['index']] . '</div>';
                    }
                    $current_column++;
                }
            }
        echo '</div>';
        
        echo '<div id="bars" >';
            $column_width = round(100 / ($limit - 1) , 3);
            $column_modular_height = round(100 / $max , 4);
            foreach($metrics as $metric) { 
                $current_column = 0;
                foreach($bars as $bar) {
                    $column_right = $current_column * $column_width;
                    if($metric['condition'] AND $metric['bar_enabled'] AND $bar['is_in_the_future'] == false) {
                        echo '<div  class="barcounter ' . $metric['class']  . '"
                                    style="height:' . ($metric['bars'][$bar['index']] * $column_modular_height ) . 'px;right:' . $column_right . '%;"
                                    title="' . $metric['bars'][$bar['index']] . '"
                                    ></div>';
                    }
                    $current_column++;
                }
            }
        echo '</div>';
        
    echo '</div>'; 


$block['chart'] = ob_get_clean();



return $block;
    
}
