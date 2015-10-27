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
      fulltext_height: 50,
      sidebar_view: "SIDEBAR: ###id###",
      print_view: "PRINT: ###id###",
      map_view: "MAP: ###id###",
      splitkey: "rex_geo",
      page_loading: "<p>loading ...</p>",
      marker_icon_normal: "",
      marker_icon_active: "",
      marker_icon_jump: ""
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

    self.initDivs();

    var myLatlng = new google.maps.LatLng(self.options.lat, self.options.lng);
    var myOptions = {
      zoom: self.options.zoom,
      center: myLatlng,
      mapTypeId: google.maps.MapTypeId.ROADMAP
    }

    self.map = new google.maps.Map(document.getElementById(self.options.map_id), myOptions);
    self.clear();
    self.getData();

    google.maps.event.addListener(self.map, 'dragend', self.refresh);
    google.maps.event.addListener(self.map, 'resize', self.refresh);
    google.maps.event.addListener(self.map, 'zoom_changed', self.refresh);

    if(self.options.fulltext == 1) {
      jQuery("#"+self.options.search_id+" a").bind("click", self.refresh );
      jQuery("#"+self.options.search_id+" input").keyup(function(event){ if(event.keyCode == 13){ jQuery("#"+self.options.search_id+" a").click(); } });

    }

  }

  self.initDivs = function() {

    jQuery("#"+self.options.div_id).addClass("rex-geo");

    self.options.all_width = parseInt(jQuery("#"+self.options.div_id).width());
    self.options.all_height = parseInt(jQuery("#"+self.options.div_id).height());

    self.options.map_width = parseInt(self.options.all_width / 3) * 2;
    self.options.map_height = self.options.all_height;

    self.options.sidebar_width = parseInt(self.options.all_width / 3);
    self.options.sidebar_height = self.options.all_height;

    if(self.options.fulltext == 1) {
      jQuery('<div class="rex-geo-search" id="' + self.options.search_id + '"><div class="rex-geo-search-box"><form action="javascript:void(0);"><span>Suche nach:</span> <input type="text" name="fulltext-search" /><a href="javascript:void(0)" onclick="return false;">ok</a></form></div></div>').appendTo("#"+self.options.div_id);
      jQuery("#"+self.options.search_id).css("width",self.options.map_width);
      jQuery("#"+self.options.search_id).css("height",self.options.fulltext_height);
      jQuery("#"+self.options.search_id).css("float","right");

      self.options.map_height = self.options.all_height-self.options.fulltext_height;
      self.options.sidebar_height = self.options.all_height;
    }

    jQuery('<div class="rex-geo-sidebar" id="' + self.options.sidebar_id + '">' + self.options.page_loading + '</div>').appendTo("#"+self.options.div_id);
    jQuery('<div class="rex-geo-map" id="' + self.options.map_id + '">' + self.options.page_loading + '</div>').appendTo("#"+self.options.div_id);
    jQuery('<div style="clear:both;height=0;"></div>').appendTo("#"+self.options.div_id);

    jQuery("#"+self.options.sidebar_id).css("width",self.options.sidebar_width);
    jQuery("#"+self.options.sidebar_id).css("height",self.options.sidebar_height);
    jQuery("#"+self.options.sidebar_id).css("overflow","auto");
    jQuery("#"+self.options.sidebar_id).css("float","left");
    jQuery("#"+self.options.sidebar_id).css("display","block");

    jQuery("#"+self.options.map_id).css("width",self.options.map_width);
    jQuery("#"+self.options.map_id).css("height",self.options.map_height);
    jQuery("#"+self.options.map_id).css("float","right");
    jQuery("#"+self.options.map_id).css("display","block");

  }

  self.getData = function() {

    search_text = "";
    if(self.options.fulltext == 1) {
      search_text = "&geo_search_text="+encodeURI(jQuery("#"+self.options.search_id+" input").val());
    }
    var currentTime = new Date();

    jQuery('#'+self.options.sidebar_id).html(self.options.page_loading);
    self.clear();

    jQuery.ajax({
      type: "GET",
      url: self.options.dataUrl+self.options.dataUrlBounds+search_text+"&geo_search_page_size="+self.options.page_size+"&geo_search_page="+self.page+"&nocache="+currentTime.getTime(),
      async: true,
      dataType: "json",
      cache: true,
      success:	function(Result) { self.data = Result; self.addMarkers(Result); },
      error: 		function() { alert("error:"+self.options.dataUrl); }
    });
  };

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
    self.setSidebar();
  };

  self.addMarker = function(data_marker) {
    var myLatlng = new google.maps.LatLng(data_marker.lat, data_marker.lng);
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
    jQuery("#"+self.marker[id]["id"]).addClass("rex-geo-side-active");
    self.marker[id]["map"].setIcon(self.options.marker_icon_active);
  }

  self.markerMouseOut = function(e) {
    id = this.rex_geo_id;
    jQuery("#"+self.marker[id]["id"]).removeClass("rex-geo-side-active");
    self.marker[id]["map"].setIcon(self.options.marker_icon_normal);
  }

  self.sidebarMouseOver = function(e) {
    id_split = this.id.split(self.options.splitkey);
    id = id_split[1];
    self.marker[id]["map"].setAnimation(google.maps.Animation.BOUNCE);
    jQuery("#"+self.marker[id]["id"]).addClass("rex-geo-side-active");
    self.marker[id]["map"].setIcon(self.options.marker_icon_active);
  }

  self.sidebarMouseOut = function(e) {
    id_split = this.id.split(self.options.splitkey);
    id = id_split[1];
    self.marker[id]["map"].setAnimation(null);
    jQuery("#"+self.marker[id]["id"]).removeClass("rex-geo-side-active");
    self.marker[id]["map"].setIcon(self.options.marker_icon_normal);
  }

  self.addSidebar = function(data_sidebar) {
    r = self.options.sidebar_view;
    jQuery.each(data_sidebar, function(index, value) {
			r = r.split("###"+index+"###").join(self.escapeHTML(value));
			r = r.split("***"+index+"***").join(escape(value));
			r = r.split("---"+index+"---").join(value);
    });
    return r;
  };

  self.addInfowindow = function(data_sidebar) {
    r = self.options.map_view;
    jQuery.each(data_sidebar, function(index, value) {
			r = r.split("###"+index+"###").join(self.escapeHTML(value));
			r = r.split("***"+index+"***").join(escape(value));
			r = r.split("---"+index+"---").join(value);
    });
    return r;
  };

  self.clear = function() {
    for (var i = 0; i < self.marker.length; ++i) {
      google.maps.event.addListener(self.marker[i]["map"], "click", function(){} );
      self.marker[i]["map"].setMap(null);
    }
    self.marker = new Array();
  };

  self.refresh = function() {
    var bounds = self.map.getBounds();
    var northEast = bounds.getNorthEast();
    var southWest = bounds.getSouthWest();
    var top = northEast.lat(), right = northEast.lng(), bottom = southWest.lat(), left = southWest.lng();
    self.options.dataUrlBounds = 	"&geo_bounds_top="+top+
                    "&geo_bounds_right="+right+
                    "&geo_bounds_bottom="+bottom+
                    "&geo_bounds_left="+left;
    self.page = 0;
    self.getData();
  };

  self.setSidebar = function() {
    self.sidebar = "";

    if(self.page > 0) {
      self.sidebar += '<li class="rex-geo-prev" id="prev-' + self.options.sidebar_id + '"><a href="javascript:void(0);">vorherige Treffer</a></li>';
    }

    for (var i = 0; i < self.marker.length; ++i) {
      self.sidebar += '<li class="rex-geo-side-normal" id="'+ self.marker[i]["id"] +'">'+self.marker[i]["sidebar"]+'</li>';
    }
    if(self.marker.length == 0){
      self.sidebar += '<li class="rex-geo-side-nothingfound">Leider wurde kein Eintrag gefunden.</li>';
    }

    if(self.data.length > self.options.page_size) {
      self.sidebar += '<li class="rex-geo-next" id="next-' + self.options.sidebar_id + '"><a href="javascript:void(0);">weitere Treffer</a></li>';
    }

    jQuery("#"+self.options.sidebar_id).html("<ul>"+self.sidebar+"</ul>");

    if(self.page > 0) { jQuery("#prev-"+self.options.sidebar_id).bind("click", i, self.getPrevData ); }
    if(self.data.length > self.options.page_size) { jQuery("#next-"+self.options.sidebar_id).bind("click", self, self.getNextData ); }

    for (var i = 0; i < self.marker.length; ++i) {
      jQuery("#"+self.marker[i]["id"]).bind("mouseover", i, self.sidebarMouseOver );
      jQuery("#"+self.marker[i]["id"]).bind("mouseout", i, self.sidebarMouseOut );
    }

  };

  self.escapeHTML = function(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  };

};

