	var ajaxurl = "/wp-admin/admin-ajax.php";
	var initTimeMs = Date.now();
	var starttime = initTimeMs;
	var sessiontime = 0;
	
	function buboSessionTimer() {
	    sessiontime += ( Math.round( ( Date.now() - starttime ) / 10 ) / 100);
	    starttime = Date.now();
	}
	
	function buboEventLog(e, eventtype) {
		action = "bubo_insights_event_log";
		eventtype = eventtype;
		touchenabled = "ontouchstart" in document.documentElement;
		scale = window.devicePixelRatio;
		screenwidth = screen.availWidth;
		screenheight = screen.availHeight;
		eventtimeMs = Date.now();
		inittime = Math.floor(initTimeMs / 1000)
		eventtime = Math.floor(eventtimeMs / 1000);
		eventwait = ( Math.round( ( Date.now() - initTimeMs ) / 10 ) / 100);
		referrer = null;
		origin = window.location.href;
		link = null;
		elementcontent = null;
		elementtag = null;
		elementclass = null;
		if( eventtype == 'pageload' ) {
			referrer = document.referrer;
		}
		else if( (eventtype == 'click') || (eventtype == 'tap') ) {
			link = e.currentTarget.href;
			elementtag = jQuery(e.target).prop("tagName").toLowerCase();
			elementclass = e.target.className;
			if(elementtag == 'img'){
			    elementcontent = jQuery(e.target).attr('src');
			}
			else {
			    elementcontent = jQuery(e.target)[0].innerText;
			}
		}
		jQuery.ajax( ajaxurl, {
			method : "POST",
			dataType : "json",
			data : { 	action: 		action,
			            inittime:       inittime,
			            sessiontime:    sessiontime,
						eventtype: 	    eventtype,
						eventtime: 	    eventtime,
						eventwait: 	    eventwait,
						touchenabled:   touchenabled,
						scale:			scale,
						screenwidth:    screenwidth,
						screenheight:   screenheight,
						referrer: 	    referrer,
						origin: 		origin,
						link: 			link,
						elementcontent: elementcontent,
						elementtag:     elementtag,
						elementclass:   elementclass
			},
			success: function(response) {
				console.log(response);
			},
			error: function(response) {
				console.log("!");				 
			}
		});
	}

    jQuery(document).ready( function(initTimex) {
		document.addEventListener("visibilitychange", () => {
			buboEventLog(null, document.visibilityState);
			if(document.visibilityState == 'hidden') {
			    buboSessionTimer();
			}
			if(document.visibilityState == 'visible') {
			    starttime = Date.now();
			}
		});
		
		buboEventLog(null, 'pageload');
        jQuery('body').on( "mousedown", "a", function(e) { buboSessionTimer(); buboEventLog(e, 'click'); } );
		jQuery('body').on( "tap", "a", function(e) { buboSessionTimer(); buboEventLog(e, 'tap'); } );
		
		window.onbeforeunload = function(e) { buboSessionTimer(); buboEventLog(null, 'unload'); };  
		
		console.log("Page loaded");
    });
