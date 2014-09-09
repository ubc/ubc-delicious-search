jQuery(document).ready(function() {
	var default_user = encodeURIComponent(jQuery('.ubc_delicious_results.resource_listings').data('user'));
	var default_limit = encodeURIComponent(jQuery('.ubc_delicious_results.resource_listings').data('limit'));
	var default_tag = encodeURIComponent(jQuery('.ubc_delicious_results.resource_listings').data('defaulttag'));
	var default_useor = encodeURIComponent(jQuery('.ubc_delicious_results.resource_listings').data('useor'));
	var reset_button =jQuery('#ubc-delicious-reset');
	var feed_url = 'http://feeds.delicious.com/v2/json/'+default_user; //left it here so that if search is destroyed, we can still use filters
	var search_base_url = 'https://avosapi.delicious.com/api/v1/posts/public/';
	var search_url = search_base_url+default_user+'/time?limit='+default_limit+'&has_all=true&tagsor='+default_useor;
	var pagination_index = 1;
	var default_pagination = encodeURIComponent(jQuery('.ubc_delicious_results.resource_listings').data('pagination'));
	
	//if reset exists, then reset form
	if (reset_button.length > 0) {
		reset_button.click(function(e) {
			//don't submit
			e.preventDefault();
			
			//reset all non select/checkbox inputs
			jQuery('.ubc-delicious-input:not(select), .ubc-delicious-input:not(:checkbox)').val('');	
			
			//reset select boxes
			jQuery('select.ubc-delicious-input').prop('selectedIndex', 0);
			
			//properly set selects with default
			jQuery('select.ubc-delicious-input option').prop('selected', function() {
				if (this.defaultSelected) {
					return this.defaultSelected;
				}
			});
			
			//properly resets checkboxes
			jQuery('.ubc-delicious-checkbox-area input').each(function(index, checkbox_client) {
				if (checkbox_client.defaultChecked) {
					this.checked = true;
				} else {
					this.checked = false;
				}
			});
			pagination_index = 1;	//resets pagination if reset button pressed
			
			//re-query again.  Since we are in reset, if it exists, then submit MUST exist
			jQuery('#ubc-delicious-submit').click();
		});
	} 

	//initial submission of query for the filterable search result area
	submit_delicious_query(search_url+'&tags='+get_all_current_tags(true));
	
	//inital loading of non-filterable/searchable search result once area
	var results_once = jQuery('.ubc_delicious_results_once');
	if (results_once.length > 0) {
		results_once.each(function(index, client) {
			var once_user = encodeURIComponent(jQuery(client).data('user')); 
			var once_limit = encodeURIComponent(jQuery(client).data('limit')); 
			var once_tag = jQuery(client).data('defaulttag');
			var once_useor = encodeURIComponent(jQuery(client).data('useor'));
			
			//clean up tags for cases where tag is a longer comma separated list of tags
			var tags = [];
			jQuery.each(once_tag.split(','), function(index, sub_client) {
				tags.push(sub_client.trim());
			})

			submit_delicious_query(search_base_url+once_user+'/time?limit='+once_limit+'&has_all=true&tagsor='+once_useor+'&tags='+encodeURIComponent(tags.join(',')), client);
		});
	}
  	/**
  	 * search submit function 
  	 * 
  	 * @param void
  	 * @return void - if results return, fill in result area with list.
  	 */
  	jQuery('#ubc-delicious-submit').click(function(e) {
		var search_term = encodeURIComponent(jQuery('#ubc-delicious-search-term').val());
		var tags = get_all_current_tags(true);
		var query_url = search_url;

		//figure out search_url
		if (search_term.length) {
			query_url = query_url + '&keywords='+search_term + (tags.length > 0 ? '&tags='+tags : '');
		} else {
			query_url = query_url + (tags.length > 0 ? '&tags='+tags.replace('+',',') : '');
		}

		submit_delicious_query(query_url);
  	});
  	  	
	/**
  	 * Makes it so that clicking on "enter" key also submits search request
  	 * 
  	 * @param void
  	 * @return void - if results return, fill in result area with list.
  	 */
  	//also take into consideration clicking on enter key
  	jQuery('#ubc-delicious-search-term').keyup(function(e) {
  		if (e.keyCode == 13) {
			pagination_index = 1;	//resets pagination if reset button pressed		
  			jQuery('#ubc-delicious-submit').click();
  		}
  	});
  	
  	/**
  	 * detects changes in dropdown values and requeries
  	 * 
  	 * @param void
  	 * @return void - if results return, fill in result area with list.
  	 */
  	jQuery('.ubc-delicious-dropdown').change(function(e) {
		pagination_index = 1;	//resets pagination if reset button pressed	

  		if (jQuery('#ubc-delicious-submit').length) {
  			jQuery('#ubc-delicious-submit').click();
  		} else {
  			var tags = get_all_current_tags(true);
  			submit_delicious_query(search_url+'&tags='+ (tags.length > 0 ? '&tags='+tags : ''));
		}
  	});

	/**
	 * detects changes in checkboxes to requery 
	 *
	 * @param void
	 * @return void
	 */
	 jQuery('.ubc-delicious-checkbox').change(function(e) {
 		pagination_index = 1;	//resets pagination if reset button pressed			
	 
  		if (jQuery('#ubc-delicious-submit').length) {
  			jQuery('#ubc-delicious-submit').click();
  		} else {
  			var tags = get_all_current_tags(true);
  			submit_delicious_query(search_url+'&tags='+ (tags.length > 0 ? '&tags='+tags : ''));
		}
  	});
  	
  	/**
  	* pagination detection!
  	*/
  	jQuery('.ubc_delicious_results').on('click', '.ubc-delicious-pagination a', function(e) {
  		pagination_index = jQuery(this).attr('href').replace(/\D/g,'');
  		if (jQuery('#ubc-delicious-submit').length) {
  			jQuery('#ubc-delicious-submit').click();
  		} else {
  			var tags = get_all_current_tags(true);
  			submit_delicious_query(search_url+'&tags='+ (tags.length > 0 ? '&tags='+tags : ''));
		}
		e.preventDefault();
	});



	/**
	 * submits based on undocumented search json api
	 * 
	 * @param String query_url - absolute url of query string
	 * @param object dom - Dom object to write results to
	 * @return void - if results return, fill in result area with list.
	 */
	function submit_delicious_query(query_url, dom) {
		//include tags
		jQuery.ajax({
	        type: 'GET',
	        url: query_url,
	        dataType: 'jsonp',
	        success: function (jsonp) {
				var return_string = '';
	        	var write_area = jQuery('.ubc_delicious_results.resource_listings', dom);
				if (typeof dom != 'undefined') {
					write_area = jQuery(dom);
				}
			
				//delete everything
	    		write_area.empty().children().remove().empty();
	    		
				//display data
	    		if (jQuery(jsonp.pkg).length == 0) {
	    			return_string += 'Sorry, no results, please broaden search parameters'
	        	} else {
					var view_type = encodeURIComponent(write_area.data('view'));
					var showcomments = write_area.data('showcomments');        	
					var sort_order = encodeURIComponent(write_area.data('sort'));

	        		//sort data according to sort_order
	        		var new_pkg = jsonp.pkg.concat();
        			switch (sort_order) {
        				case 'alpha':
        					new_pkg.sort(function(a,b) {
								var A = a.title.toLowerCase();
								var B = b.title.toLowerCase();
								if (A < B){
								   return -1;
								}else if (A > B){
								  return  1;
								}
								return 0;
        					});
        					break;
        				case 'rank':
        				default:
        					new_pkg.sort(function(a,b) {
        						return parseInt(b.save_rank) - parseInt(a.save_rank)
        					});
        					break;
					}
					
					//need some logic to handle pagination
					var i, pagination_html;
					var temp = [];

					if (typeof write_area.data('pagination') !== 'undefined' && parseInt(write_area.data('pagination')) > 0) {
						
						for (i = 0; i < new_pkg.length; i = i + parseInt(default_pagination)) {
							i = parseInt(i);
							temp.push(new_pkg.slice(i, i+parseInt(default_pagination)));
						}
						
						if (temp.length > 1) {	//only show pagination IF # returned > pagination
							pagination_html = '<div class="ubc-delicious-pagination pagination pagination-centered"><ul class="page-numbers">';

							for (i = 1; i <= temp.length; i++) {
								pagination_html += '<li'+((pagination_index == i)? ' class="active"' : '')+'><a href="#p-'+i+'">'+i+'</a></li>'
							} 
							pagination_html += '</ul></div>';
						}
						new_pkg = temp[pagination_index-1];	//zero based array with 1 based pagination...
					}

	        		//create links
	        		switch (view_type) {
	        			case 'links':
							jQuery.each(new_pkg, function(index, client) {
				        		var title= client.title;
				        		var linkURL = client.url;
				        		var comments = '<ul class="ubc_delicious_comments"><li>'+client.note+'</li></ul>';
								return_string += '<a target="_blank" href="'+linkURL+'">'+title+'</a><br>'+(showcomments? comments : '' );
							});
							break;
	        			case 'list':
	        			case 'list_unordered':
	        			case 'list_ordered':
						default:
	        				if (view_type != 'list_ordered') {
	        					return_string += '<ul>';
	        				} else {
	        					return_string += '<ol>';
							}
							
							jQuery.each(new_pkg, function(index, client) {
				        		var title= client.title;
				        		var linkURL = client.url;
			        			var comments = '<ul class="ubc_delicious_comments"><li>'+client.note+'</li></ul>';
								return_string += '<li><a target="_blank" href="'+linkURL+'">'+title+'</a>'+(showcomments? comments : '' )+'</li>';
							});
							
							if (view_type != 'list_ordered') {
	        					return_string += '</ul>';
	        				} else {
	        					return_string += '</ol>';
							}
	        				break;
					}				
		        }
		        
		        if (typeof write_area.data('pagination') !== 'undefined' && parseInt(write_area.data('pagination')) > 0 && pagination_html) {
		        	return_string += pagination_html;
	        	}
	        	
		        write_area.append(return_string);
	        }
	    });
    }

	/**
	 * Pulls all tags from every dropdown and combine them into a string
	 *
	 * @param boolean use_default - determines whether to use default tag or not
	 * @return string - comma separated string of tags
	 *
	 */
	function get_all_current_tags(use_default) {
		use_default = typeof use_default !== 'undefined' ? use_default : true;
	
		var tags = [];
		var selectz = jQuery('.ubc-delicious-dropdown');
		var checkz = jQuery('.ubc-delicious-checkbox');
		jQuery.each(selectz, function(index, client) {
			var select_val = jQuery(client).val().trim();
			if (select_val != 'Show All') {
				tags.push(encodeURIComponent(select_val.trim()));
			}
		});
		jQuery.each(checkz, function(index, client) {
			if (client.checked) {
				tags.push(encodeURIComponent(jQuery(client).val().trim()));
			}
		});
		
		//if no options are selected, then it will default to the page's default tag (aka unit tag)
		if (use_default) {
			tags.push(default_tag);
		}

		return tags.join(',');  		
	}
});
