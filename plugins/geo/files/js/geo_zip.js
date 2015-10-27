
function split( val ) {
	return val.split( /,\s*/ );
}

function extractLast( term ) {
	return split( term ).pop();
}

$.extend($.ui.autocomplete.prototype.options, {
	open: function(event, ui) {
		$(this).autocomplete("widget").css({
            "width": ($(this).outerWidth() + "px")
        });
    }
});


var rex_yform_geomap = function(loaded_options) {

	var self = this;

	self.options = {
			div_id: "rex-googlemap",
			lat: 51.133333,
			lng: 10.416667,
			zoom: 5,
			page_size: 50,
			position: 0,
			dataUrl: "",
			dataUrlBounds: "",
			fulltext: 0,
			fulltext_height: 60,
			splitkey: "rex_geo",
			page_loading: "<p>loading ...</p>",
			marker_icon_normal: "",
			marker_icon_active: "",
			marker_icon_jump: "",
			norefresh: 0,
			clearinputs: 1
		};

	if (loaded_options) {
		  self.options = jQuery.extend(self.options, loaded_options);
	}

	self.map = null;
	self.marker = new Array();
	self.data = null;
	self.page = 0;
	self.infoWindow = new google.maps.InfoWindow;
	
	self.initialize = function()	{

		self.options.map_id = self.options.div_id+"-map";
		self.options.sidebar_id = self.options.div_id+"-sidebar";
		self.options.search_id = self.options.div_id+"-search";
		self.options.searchall = 1;

		self.initDivs();

		var myLatlng = new google.maps.LatLng(self.options.lat, self.options.lng);
		var myOptions = {
			zoom: self.options.zoom,
			center: myLatlng,
			mapTypeControlOptions: {
        mapTypeIds: [google.maps.MapTypeId.ROADMAP] //, 'map_style'
      }
		}

  	self.map = new google.maps.Map(document.getElementById(self.options.map_id), myOptions);

    /*
    var stylesArray = [
      {
        stylers: [
          { visibility: "simplified" },
          { hue: "#ff0900" },
          { lightness: 47 },
          { saturation: -32 },
          { gamma: 0.6 }
        ]
      }
    ];

    var styledMap = new google.maps.StyledMapType( stylesArray, { name: "Styled Map" });

    self.map.mapTypes.set('map_style', styledMap);
    self.map.setMapTypeId('map_style');
    */

    // ---------- / bss map

		self.clear();
		self.getData();

		google.maps.event.addListener(self.map, 'dragend', self.refresh);
		google.maps.event.addListener(self.map, 'resize', self.refresh);
		google.maps.event.addListener(self.map, 'zoom_changed', self.refresh);

		// jQuery("#"+self.options.search_id+" a").bind("click", self.refresh );
		// jQuery("#"+self.options.search_id+" input").keyup(function(event){ if(event.keyCode == 13){ jQuery("#"+self.options.search_id+" a").click(); } });

    jQuery( window ).resize(function() {
        map_explorer.refreshSizes();
    });


	}

	self.initDivs = function() {
	
		jQuery("#"+self.options.div_id).addClass("rex-geo");
	
		main_header = '<header>'
+'          <div class="rex-geo-row">'
+'              <div class="rex-geo-col-sm-2 rex-geo-hidden-xs">'
+'                  <button class="rex-geo-button rex-geo-button-reset">'
+'                      <i class="rex-geo-icon rex-geo-icon-reset"></i>'
+'                      <span class="rex-geo-text">zurück setzen</span>'
+'                  </button>'
+'              </div>'
+''
+'              <div class="rex-geo-col-xs-12 rex-geo-col-sm-4">'
+'                  <div class="rex-geo-input-group">'
+'                      <input type="text" placeholder="PLZ (mind. 3 Zeichen)" id="rex-geo-plz" />'
+'                      <span class="rex-geo-input-group-button">'
+'                          <button class="rex-geo-button rex-geo-button-submit">'
+'                              <i class="rex-geo-icon rex-geo-icon-refresh"></i>'
+'                              <span class="rex-geo-text">aktualisieren</span>'
+'                          </button>'
+'                      </span>'
+'                  </div>'
+'              </div>'
+''
+'              <div class="rex-geo-hidden-xs rex-geo-col-sm-6">'
+'                  <div class="rex-geo-input-group">'
+'                      <input type="text" placeholder="Stadt (mind. 3 Zeichen)" id="rex-geo-city" />'
+'                      <span class="rex-geo-input-group-button">'
+'                          <button class="rex-geo-button rex-geo-button-submit">'
+'                              <i class="rex-geo-icon rex-geo-icon-refresh"></i>'
+'                              <span class="rex-geo-text">aktualisieren</span>'
+'                          </button>'
+'                      </span>'
+'                  </div>'
+'              </div>'
+'          </div>'
+''
+'      </header>';
		
    main_footer = '<div class="rex-geo-map" id="' + self.options.map_id + '">' + self.options.page_loading + '</div>';

		main = '<section class="rex-geo-main">' + main_header + main_footer + '</section>';
		
		jQuery(main).appendTo("#"+self.options.div_id);

    jQuery('.rex-geo-button-reset').bind("click", function(e) { 
      jQuery('#rex-geo-plz').val('');
      jQuery('#rex-geo-city').val('');
    });
    jQuery('#rex-geo-plz').parent().find('.rex-geo-button-submit').bind("click", function() {
        var e = jQuery.Event( 'keydown', { which: 13, keyCode: 13 } );
        jQuery('#rex-geo-plz').trigger(e);
    } );
    jQuery('#rex-geo-city').parent().find('.rex-geo-button-submit').bind("click", function() {
        var e = jQuery.Event( 'keydown', { which: 13, keyCode: 13 } );
        jQuery('#rex-geo-city').trigger(e);
    } );

    jQuery('#rex-geo-plz').bind('focus', function(d) {
      jQuery('#rex-geo-plz').val('');
      jQuery('#rex-geo-city').val('');
    });

    jQuery('#rex-geo-city').bind('focus', function(d) {
      jQuery('#rex-geo-plz').val('');
      jQuery('#rex-geo-city').val('');
    });

		jQuery('<aside class="rex-geo-sidebar" id="' + self.options.sidebar_id + '">' + self.options.page_loading + '</aside>').appendTo("#"+self.options.div_id);
	
		self.options.all_width = parseInt(jQuery("#"+self.options.div_id).outerWidth());
		self.options.all_height = parseInt(jQuery("#"+self.options.div_id).outerHeight());
	
    self.options.map_width = parseInt(self.options.all_width / 3) * 2;

    header_height = parseInt(jQuery("#"+self.options.div_id+" .rex-geo-main header").outerHeight());

    self.options.map_height = self.options.all_height - header_height;

    if (self.options.map_height < 200) {
        self.options.map_height = 200;
    }
	
		// jQuery("#"+self.options.map_id).css("width", self.options.map_width);
		jQuery("#"+self.options.map_id).css("height", self.options.map_height);

		$("#rex-geo-plz")
			// don t navigate away from the field on tab when selecting an item
			.bind( "keydown", function( event ) {

        if ( event.keyCode === $.ui.keyCode.ENTER && !$( this ).data( "autocomplete" ).menu.active) {
          
          term = $("#rex-geo-plz").val();
          if ( term.length < 3 ) {
            self.alert('Bitte mindestens 3 Zeichen eingeben');

          } else {
            // TODO           

            // background: url("../images/loading.gif") #fff no-repeat 50% 50%;
            // TODO: loading aktivieren
            
            $.getJSON( self.options.plzUrl, {
  						plz: term
  					}, function(response) {

              if (response.length > 0) {
                $("#rex-geo-plz").val(response[0].id);
      					$("#rex-geo-city").val(response[0].value);
      					
      					var myLatlng = new google.maps.LatLng(response[0].lat, response[0].lng);
      
      					self.options.clearinputs = 0;
      					self.options.norefresh = 1;
      				
      					self.map.panTo(myLatlng);
      					self.map.setZoom(10);
      					
      					self.options.norefresh = 0;
      					self.refresh();
      					self.options.clearinputs = 1;
					
					      return false;

              } else {
              
                self.alert('Keinen Eintrag gefunden');
              }

  					} );
            
            event.preventDefault();

          }
          
        } else if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active ) {
					// event.preventDefault();
				}
			})
			.autocomplete({
				source: function( request, response ) {
					$.getJSON( self.options.plzUrl, {
						plz: extractLast( request.term )
					}, response );
				},
				search: function() {
					var term = extractLast( this.value );
					if ( term.length < 3 ) { return false; }
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
				
					// var terms = split( this.value );
					// remove the current input
					// terms.pop();
					// add the selected item
					// terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					// terms.push( "" );
					// this.value = terms.join( ", " );
					
					$("#rex-geo-plz").val(ui.item.id);
					$("#rex-geo-city").val(ui.item.value);
					
					var myLatlng = new google.maps.LatLng(ui.item.lat, ui.item.lng);

					self.options.clearinputs = 0;
					self.options.norefresh = 1;
				
					self.map.panTo(myLatlng);
					self.map.setZoom(10);
					
					self.options.norefresh = 0;
					self.refresh();
					self.options.clearinputs = 1;
					
					return false;
				}
			});

		$("#rex-geo-city")
			.bind( "keydown", function( event ) {
			
			  if ( event.keyCode === $.ui.keyCode.ENTER && !$( this ).data( "autocomplete" ).menu.active) {
          
          term = $("#rex-geo-city").val();
          if ( term.length < 3 ) {
            self.alert('Bitte mindestens 3 Zeichen eingeben');

          } else {
            
            $.getJSON( self.options.cityUrl, {
  						city: term
  					}, function(response) {

              if (response.length > 0) {
                $("#rex-geo-plz").val(response[0].id);
      					$("#rex-geo-city").val(response[0].value);
      					
      					var myLatlng = new google.maps.LatLng(response[0].lat, response[0].lng);
      
      					self.options.clearinputs = 0;
      					self.options.norefresh = 1;
      				
      					self.map.panTo(myLatlng);
      					self.map.setZoom(10);
      					
      					self.options.norefresh = 0;
      					self.refresh();
      					self.options.clearinputs = 1;
					
					      return false;

              } else {
              
                self.alert('Keinen Eintrag gefunden');
              }

  					} );
            
            
            
            
          }
          
        } else if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active ) {
					// event.preventDefault();
				}
			})
			.autocomplete({
				source: function( request, response ) {
					$.getJSON( self.options.cityUrl, {
						city: extractLast( request.term )
					}, response );
				},
				search: function() {
					var term = extractLast( this.value );
					if ( term.length < 3 ) { return false; }
				},
				focus: function() { return false; },
				select: function( event, ui ) {
					
					// var terms = split( this.value );
					//terms.pop();
					//terms.push( ui.item.value );
					//terms.push( "" );
					//this.value = terms.join( ", " );
					
					$("#rex-geo-plz").val(ui.item.id);
					$("#rex-geo-city").val(ui.item.value);
					
					var myLatlng = new google.maps.LatLng(ui.item.lat, ui.item.lng);
					
					self.options.clearinputs = 0;
					self.options.norefresh = 1;
				
					self.map.panTo(myLatlng);
					self.map.setZoom(10);
					
					self.options.norefresh = 0;
					self.refresh();
					self.options.clearinputs = 1;
					
					return false;
				}
			});


	}

	self.getData = function() {
	
		search_text = "";
		search_text = "&geo_search_text="+encodeURI(jQuery("#"+self.options.search_id+" input").val())+"&geo_search_zoom="+self.map.getZoom();

		var currentTime = new Date();
		
		jQuery('#'+self.options.sidebar_id).html(self.options.page_loading);
		self.clear();
		
		search_zip = "";
		search_zip = "&geo_search_plz="+encodeURI(jQuery("#rex-geo-plz").val());
		
		jQuery.ajax({
			type: "GET",
			url: self.options.dataUrl+self.options.dataUrlBounds+search_text+search_zip+"&geo_search_page_size="+self.options.page_size+"&geo_search_page="+self.page+"&searchall="+self.options.searchall+"&nocache="+currentTime.getTime(),
			async: true,
			dataType: "json",
			cache: true,
			success:	function(Result) {   	
			  self.clear();
			  self.data = Result.data; 
			  self.addMarkers(Result.data); 
			},
			error: function() { 
			  self.alert("error: "+self.options.dataUrl);
			}
		});
	};
	
	self.getPrintData = function() {

    var w = window.open('', 'map-print', 'width=500,height=600,scrollbars=yes,toolbar=yes,top=100,left=100');

    html = '		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
        +'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">'
        +'	<head>'
        +'	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
        +'	<title>PopUp Druckadressen</title>'
        +'	<meta name="language" content="de" />'
        +'	<style>'
        +'  p,h2 { font-size: 12px; font-family: Arial, Verdana; }  '
        +'  p { border-bottom: 1px solid #333; padding-bottom: 10px; }'
        +'  h2 { font-size: 16px; }'
        +'  </style>'
        +'	</head>'
        +'	<body><h2>Druckansicht</h2>';
    
    imax = self.data.length;
		if(imax > self.options.page_size) imax = self.options.page_size;
		
		self.bounds = new google.maps.LatLngBounds();
		
		for (var i = 0; i < imax; ++i) {
      html += self.data[i]['print_view'];
		}
    
    html += '	</body>'
        +' </html>';

    jQuery(w.document.body).html(html);
    w.print();
    event.preventDefault();

    return false;

	}
	
	self.getNextData = function() {
		self.page = self.page + 1;
		self.getData();
	}

	self.getPrevData = function() {
		if(self.page > 0) { 
			self.page = self.page - 1;
			self.getData();
		}
	}
	
	self.addMarkers = function(markers_data) {
		self.data = markers_data;
		imax = self.data.length;
		if(imax > self.options.page_size) imax = self.options.page_size;
		
		self.bounds = new google.maps.LatLngBounds();
		
		for (var i = 0; i < imax; ++i) {
			self.marker[i] = new Array();
			self.marker[i]["id"] = self.options.div_id + self.options.splitkey + i;
			self.marker[i]["map"] = self.addMarker(self.data[i]);
			self.marker[i]["map"].rex_geo_id = i;
			self.marker[i]["sidebar"] = self.addSidebar(self.data[i]);
			self.marker[i]["infowindow"] = self.addInfowindow(self.data[i]);

			google.maps.event.addListener(self.marker[i]["map"], "click", self.showMarkerInfo );
			google.maps.event.addListener(self.marker[i]["map"], "mouseover", self.markerMouseOver );
			google.maps.event.addListener(self.marker[i]["map"], "mouseout", self.markerMouseOut );
		}

		// mapzomm refresh off
			// google.maps.event.clearListeners(self.map, 'zoom_changed');
  	 	// self.map.fitBounds(self.bounds);
  	 		// google.maps.event.addListener(self.map, 'zoom_changed', self.refresh);
			// mapzomm refresh off

		self.setSidebar();
	};
	
	self.addMarker = function(data_marker) {
		var myLatlng = new google.maps.LatLng(data_marker.lat, data_marker.lng);
		
		self.bounds.extend(myLatlng);

		return new google.maps.Marker({
	        position: myLatlng, 
	        map: self.map,
	        icon: self.options.marker_icon_normal
	    }); 
	};

	self.showMarkerInfo = function(e) {
		id = this.rex_geo_id;
		self.infoWindow.setContent(self.marker[id]["infowindow"] );
		self.infoWindow.open(self.map, self.marker[id]["map"]);
	};	

	self.markerMouseOver = function(e) {
		id = this.rex_geo_id;
		jQuery("#"+self.marker[id]["id"]).addClass("rex-geo-active");
		self.marker[id]["map"].setIcon(self.options.marker_icon_active);
	}

	self.markerMouseOut = function(e) {
		id = this.rex_geo_id;
		jQuery("#"+self.marker[id]["id"]).removeClass("rex-geo-active");
		self.marker[id]["map"].setIcon(self.options.marker_icon_normal);
	}

	self.sidebarMouseOver = function(e) {
		id_split = this.id.split(self.options.splitkey); 
		id = id_split[1];
		self.marker[id]["map"].setAnimation(google.maps.Animation.BOUNCE);
		jQuery("#"+self.marker[id]["id"]).addClass("rex-geo-active");
		self.marker[id]["map"].setIcon(self.options.marker_icon_active);
	}

	self.sidebarMouseOut = function(e) {
		id_split = this.id.split(self.options.splitkey); 
		id = id_split[1];
		self.marker[id]["map"].setAnimation(null);
		jQuery("#"+self.marker[id]["id"]).removeClass("rex-geo-active");
		self.marker[id]["map"].setIcon(self.options.marker_icon_normal);
	}

	self.addSidebar = function(d_data) {
    return d_data['sidebar_view'];
	};

	self.addInfowindow = function(d_data) {
		return d_data['map_view'];
	};

	self.clear = function() {
		for (var i = 0; i < self.marker.length; ++i) {
			google.maps.event.addListener(self.marker[i]["map"], "click", function(){} );
			self.marker[i]["map"].setMap(null);
		}
		self.marker = new Array();
	};

	self.refresh = function() {
	
		if(self.options.norefresh == 1)
			return;
	
		var bounds = self.map.getBounds();
		var northEast = bounds.getNorthEast();
		var southWest = bounds.getSouthWest();
		var top = northEast.lat(), right = northEast.lng(), bottom = southWest.lat(), left = southWest.lng();
		self.options.dataUrlBounds = 	"&geo_bounds_top="+top+
										"&geo_bounds_right="+right+
										"&geo_bounds_bottom="+bottom+
										"&geo_bounds_left="+left;
		self.page = 0;
		self.options.searchall = 0;
		self.getData();

		if(self.options.clearinputs == 1) {
			$("#rex-geo-plz").val("");
			$("#rex-geo-city").val("");
		}

	};

  self.refreshSizes = function() {
  
      // refresh sizes



      // refresh message box
      if (jQuery(".rex-geo-message")) {
        jQuery(".rex-geo-message").css('position', 'absolute');
        jQuery(".rex-geo-message").css('background-image', 'url("/layout_map/images/bg70.png")');
        jQuery(".rex-geo-message .table-cell").css('display', 'table-cell');
        jQuery(".rex-geo-message .table-cell").css('text-align', 'center');
        jQuery(".rex-geo-message .table-cell").css('vertical-align', 'middle');
        jQuery(".rex-geo-message .table-cell span").css('background', '#fff');
        jQuery(".rex-geo-message .table-cell span").css('padding', '20px');
        jQuery(".rex-geo-message .table-cell").height(jQuery("#"+self.options.div_id).height());
        jQuery(".rex-geo-message .table-cell").width(jQuery("#"+self.options.div_id).width());
      }
  }


	self.setSidebar = function() {

		self.sidebar = "";
    self.sidebar += '<header>';
    
    if (self.marker.length > 0){
      self.sidebar += '<div class="rex-geo-print">';
      self.sidebar += '<a href="javascript:void(0);" class="print-' + self.options.sidebar_id + '"><i class="rex-geo-icon rex-geo-icon-list"></i>Trefferliste ausdrucken</a>';
      self.sidebar += '</div>';
    }
    
    pager = '';
    pager += '<nav class="rex-geo-pager">';
    pager += '           <ul>';
    if(self.page > 0) {
      pager += '<li class="rex-geo-prev"><a href="javascript:void(0);" class="prev-' + self.options.sidebar_id + '"><i class="rex-geo-icon rex-geo-icon-pager-prev"></i>vorherige Treffer</a></li>';
    }
    if(self.data.length > self.options.page_size) {
      pager += '                  <li class="rex-geo-next"><a href="javascript:void(0);" class="next-' + self.options.sidebar_id + '">weitere Treffer<i class="rex-geo-icon rex-geo-icon-pager-next"></i></a></li>';
    }
    pager += '</ul>';
    pager += '</nav>';

    self.sidebar += pager;

    self.sidebar += '</header>';
        

    // ----- results
    self.sidebar += '<div class="rex-geo-result"> <ul class="rex-geo-result-list">';
  
    for (var i = 0; i < self.marker.length; ++i) { 
			self.sidebar += '<li id="'+ self.marker[i]["id"] +'">'+self.marker[i]["sidebar"]+'</li>';
			// <li class="rex-geo-active">. . .</li>
		}

		if (self.marker.length == 0){ 
			self.sidebar += '<li class="rex-geo-nothingfound">Leider wurde kein Eintrag gefunden.</li>';
		}
    self.sidebar += '   </ul></div>';

		jQuery("#"+self.options.sidebar_id).html(self.sidebar);

		if(self.page > 0) { 
		  jQuery(".prev-"+self.options.sidebar_id).bind("click", i, self.getPrevData ); 
		}
		if(self.data.length > self.options.page_size) { 
		  jQuery(".next-"+self.options.sidebar_id).bind("click", self, self.getNextData ); 
		}

    if(self.marker.length > 0){ 
      jQuery(".print-"+self.options.sidebar_id).bind("click", self, self.getPrintData );
    }

		for (var i = 0; i < self.marker.length; ++i) {
			jQuery("#"+self.marker[i]["id"]).bind("mouseover", i, self.sidebarMouseOver );
			jQuery("#"+self.marker[i]["id"]).bind("mouseout", i, self.sidebarMouseOut );
		}

	};
	
	self.alert = function(message) {
	
	  alert_div = '<div class="rex-geo-message"><div class="table-cell"><span>' + message + '</span></div></div>';
	  jQuery(alert_div).appendTo("#"+self.options.div_id);
	  jQuery('.rex-geo-message').bind("click", function() {
	      jQuery('.rex-geo-message').remove();
	  })
    self.refreshSizes();
	
	}
	
	
	
	
	self.escapeHTML = function(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };
	

};
