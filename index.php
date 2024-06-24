<?php
/**
* Plugin Name: Door products importer
* Description: System to import the door products.
* Version: 0.1
**/


class import_door_data {

    public function __construct() {
        // AJAX actions for the residential door products
        add_action( 'wp_ajax_import_residential_doors',array( $this, 'import_residential_doors' ) );
        add_action( 'wp_ajax_nopriv_import_residential_doors',array( $this, 'import_residential_doors' ) );

        // AJAX actions for the door accessories
        add_action( 'wp_ajax_import_doors_acc',array( $this, 'import_doors_acc' ) );
        add_action( 'wp_ajax_nopriv_import_doors_acc',array( $this, 'import_doors_acc' ) );

        // AJAX actions for the commercial door products
        add_action( 'wp_ajax_import_commercial_doors',array( $this, 'import_commercial_doors' ) );
        add_action( 'wp_ajax_nopriv_import_commercial_doors',array( $this, 'import_commercial_doors' ) );

        // AJAX actions for processing the product content
        add_action( 'wp_ajax_import_details_data',array( $this, 'import_details_data' ) );
        add_action( 'wp_ajax_nopriv_import_details_data',array( $this, 'import_details_data' ) );

        // AJAX actions to process all products
        add_action( 'wp_ajax_import_all_data',array( $this, 'import_all_data' ) );
        add_action( 'wp_ajax_nopriv_import_all_data',array( $this, 'import_all_data' ) );

        // AJAX actions to process the subtitle on the product page
        add_action( 'wp_ajax_import_all_subtitles',array( $this, 'import_all_subtitles' ) );
        add_action( 'wp_ajax_nopriv_import_all_subtitles',array( $this, 'import_all_subtitles' ) );

        // shortcode to display the brochures section
        add_shortcode( 'products_brochure_section', array( $this, 'products_brochure_section' ));
        // shortcode to display the resources section
        add_shortcode( 'products_resources_section', array( $this, 'products_resources_section' ));

    }

    function products_brochure_section( $atts, $content = null ) {
        global $post;

        if($atts["prod_id"]){
            $prod_id = $atts["prod_id"];
        }else{
            $prod_id = $post->ID;
        }

        /* process all the brochures in the ACF field */
        if(get_post_meta($prod_id,"brochures",true)){
            $cont = '<ul id="brochure_list">';
            for($i=0;$i<get_post_meta($prod_id,"brochures",true);$i++){
                 /* display brochure image, text and link */
                $cont .= '<li><div class="brochure_list_item_image"><a href="'.get_post_meta($prod_id,"brochures_".$i."_file_url",true).'" target="_blank">'.wp_get_attachment_image(get_post_meta($prod_id,"brochures_".$i."_image",true),"medium").'</a></div><div class="brochure_list_item_name"><a href="'.get_post_meta($prod_id,"brochures_".$i."_file_url",true).'" target="_blank">'.get_post_meta($prod_id,"brochures_".$i."_name",true).'</a></div></li>';
            }
            $cont .= '</ul>';
        }else{
            $cont = '';
        }

        return $cont;
    }

    function products_resources_section( $atts, $content = null ) {
        global $post;

        if($atts["prod_id"]){
            $prod_id = $atts["prod_id"];
        }else{
            $prod_id = $post->ID;
        }

        /* process all the resources in the ACF field */
        if(get_post_meta($prod_id,"resources",true)){
            $cont = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" /><ul id="resources_list">';
            /* process 1st level resources list */
            for($i=0;$i<get_post_meta($prod_id,"resources",true);$i++){
                $cont .= '<li><div class="resource_list_section">';
                if(get_post_meta($prod_id,"resources_".$i."_resource_categories",true)){
                    $cont .= '<ul class="resource_list_section_categories">';
                     /* process 2nd level resources list */
                    for($j=0;$j<get_post_meta($prod_id,"resources_".$i."_resource_categories",true);$j++){
                        $cont .= '<li><div class="resource_list_section_category">'.get_post_meta($prod_id,"resources_".$i."_resource_categories_".$j."_category_name",true).'</div>';
                        if(get_post_meta($prod_id,"resources_".$i."_resource_categories_".$j."_files",true)){
                            $cont .= '<ul class="resource_list_section_categories_files">';
                             /* process 3rd level resources list */
                            for($k=0;$k<get_post_meta($prod_id,"resources_".$i."_resource_categories_".$j."_files",true);$k++){
								$file_url = get_post_meta($prod_id,"resources_".$i."_resource_categories_".$j."_files_".$k."_file_url",true);
                                /* display icon based on file format */
								if(strpos($file_url,".pdf")){
									$file_icon = '<i class="fa-regular fa-file-pdf"></i>';
								}elseif(strpos($file_url,".doc") || strpos($file_url,".docx")){
									$file_icon = '<i class="fa-regular fa-file-word"></i>';
								}else{
									$file_icon = '<i class="fa-regular fa-file"></i>';
								}
                                /* display resource icon, name & link */
                                $cont .= '<li><div class="resource_list_section_file"><a href="'.$file_url.'" target="_blank">'.$file_icon." ".get_post_meta($prod_id,"resources_".$i."_resource_categories_".$j."_files_".$k."_name",true).'</a></div></li>';
                            }
                            $cont .= '</ul>';
                        }
                        $cont .= '</li>';
                    }
                    $cont .= '</ul>';
                }
                $cont .= '</div></li>';
            }
            $cont .= '</ul>';
        }else{
            $cont = '';
        }

        return $cont;
    }

    function import_doors_acc(){
        global $wpdb;
        
        /* create the categories for the door accessories */
        $residential_acc = array(
            "Residential Garage Door Openers" => "https://www.overheaddoor.com/residential/shop-openers",
            "Garage Door Opener Accessories" => "https://www.overheaddoor.com/residential/shop-accessories"
        );
        $commercial_acc = array(
            "Commercial Operators" => "https://www.overheaddoor.com/commercial/shop-operators",
            "Commercial Accessories" => "https://www.overheaddoor.com/commercial/shop-accessories",
            "Loading Dock Equipment" => "https://www.overheaddoor.com/commercial/shop-dock-equipment",
        );
        $residential_term = get_term_by("name","Residential","product-category");
        if(!$residential_term){
            wp_insert_term("Residential","product-category");
            $residential_term = get_term_by("name","Residential","product-category");
        }
        $residential_doors_term = get_term_by("name","All Residential Garage Doors","product-category");
        if(!$residential_doors_term){
            wp_insert_term("All Residential Garage Doors","product-category",array("parent"=>$residential_term->term_id));
        }
        $commercial_term = get_term_by("name","Commercial","product-category");
        if(!$commercial_term){
            wp_insert_term("Commercial","product-category");
            $commercial_term = get_term_by("name","Commercial","product-category");
        }
        $commercial_doors_term = get_term_by("name","All Commercial Doors","product-category",array("parent"=>$commercial_term->term_id));
        if(!$commercial_doors_term){
            wp_insert_term("All Commercial Doors","product-category");
        }

        /* parse the pages for residential door accessories */
        foreach($residential_acc as $term_name=>$the_link){
            $the_term = get_term_by("name",$term_name,"product-category");
            if(!$the_term){
                wp_insert_term($term_name,"product-category",array("parent"=>$commercial_term->term_id));
                $the_term = get_term_by("name",$term_name,"product-category");
            }
            // get page content via cURL
            $residential_content = $this->import_curl_page($the_link);

            // go through all product cards
            preg_match_all('/<article class=\"door-card door-card--secondary\">(.*?)<\/article>/s', $residential_content, $matches);
            $products = ($matches[1]);
            foreach($products as $product){
                // get product name
                preg_match('/<h3 class=\"h5 door-card__title\">(.*?)<\/h3>/s', $product, $matches);
                $the_title = trim($matches[1]);
                // get product subtitle
                preg_match('/<h4 class=\"door-card__sub-heading\">(.*?)<\/h4>/s', $product, $matches);
                $the_subtitle = trim($matches[1]);

                // create identifier
                $identifier = $the_title."-".$the_subtitle;

                // check if product exists
                if($pid = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='prod_identifier' AND `meta_value`='".$identifier."'")){
                    // if product exists, update post
                    $my_post = array(
                        'ID'            => $pid,
                        'post_title'    => wp_strip_all_tags($the_title),
                        'post_status'   => 'publish',
                        'post_type'     => 'product',
                    );
                        
                    wp_update_post( $my_post );
                }else{
                    // if product does not exist, create the post
                    $my_post = array(
                        'post_title'    => wp_strip_all_tags($the_title),
                        'post_status'   => 'publish',
                        'post_type'     => 'product',
                    );
                        
                    $pid = wp_insert_post( $my_post );
                    update_post_meta($pid,"prod_identifier",$identifier);
                }
                // update subtitle
				update_post_meta($pid,"subtitle",$the_subtitle);
                // set product category
                wp_set_object_terms($pid, $the_term->term_id, "product-category", 1);

                // get attribute table values
                preg_match_all('/<dt class=\"door-card__dt\ ">(.*?)<\/dt>/s', $product, $matches_fields);
                preg_match_all('/<dd class=\"door-card__dd \">(.*?)<\/dd>/s', $product, $matches_values);
                $meta_values = array();
                foreach($matches_fields[1] as $i=>$v){
                    $meta_values[str_replace(":","",trim($v))] = trim($matches_values[1][$i]);
                }
                update_post_meta($pid,"meta_values",$meta_values);

                // get product gallery images
                preg_match_all('/<img src="(.*?)"/s', $product, $matches_gallery);
                update_post_meta($pid,"meta_gallery",$matches_gallery[1]);

                preg_match_all('/href="(.*?)"/s', $product, $matches_links);
                $the_link = "https://www.overheaddoor.com".trim($matches_links[1][0]);

                // update product page URL
                update_post_meta($pid,"the_link",$the_link);

                // get & save product page content
                if(!get_post_meta($pid,"the_details_page",true)){
                    $details_page = $this->import_curl_page($the_link);
                    update_post_meta($pid,"the_details_page",$details_page);
                }
            }
        }

        /* parse the pages for commercial door accessories */
        foreach($commercial_acc as $term_name=>$the_link){
            $the_term = get_term_by("name",$term_name,"product-category");
            if(!$the_term){
                wp_insert_term($term_name,"product-category",array("parent"=>$commercial_term->term_id));
                $the_term = get_term_by("name",$term_name,"product-category");
            }
            // get page content via cURL
            $residential_content = $this->import_curl_page($the_link);

            // go through all product cards
            preg_match_all('/<article class=\"door-card door-card--secondary\">(.*?)<\/article>/s', $residential_content, $matches);
            $products = ($matches[1]);
            foreach($products as $product){
                // get product name
                preg_match('/<h3 class=\"h5 door-card__title\">(.*?)<\/h3>/s', $product, $matches);
                $the_title = trim($matches[1]);
                // get product subtitle
                preg_match('/<h4 class=\"door-card__sub-heading\">(.*?)<\/h4>/s', $product, $matches);
                $the_subtitle = trim($matches[1]);

                // create identifier
                $identifier = $the_title."-".$the_subtitle;

                // check if product exists
                if($pid = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='prod_identifier' AND `meta_value`='".$identifier."'")){
                    // if product exists, update post
                    $my_post = array(
                        'ID'            => $pid,
                        'post_title'    => wp_strip_all_tags($the_title),
                        'post_status'   => 'publish',
                        'post_type'     => 'product',
                    );
                        
                    wp_update_post( $my_post );
                }else{
                    // if product does not exist, create the post
                    $my_post = array(
                        'post_title'    => wp_strip_all_tags($the_title),
                        'post_status'   => 'publish',
                        'post_type'     => 'product',
                    );
                        
                    $pid = wp_insert_post( $my_post );
                    update_post_meta($pid,"prod_identifier",$identifier);
                }
                // update subtitle
				update_post_meta($pid,"subtitle",$the_subtitle);
                // set product category
                wp_set_object_terms($pid, $the_term->term_id, "product-category", 1);

                // get attribute table values
                preg_match_all('/<dt class=\"door-card__dt\ ">(.*?)<\/dt>/s', $product, $matches_fields);
                preg_match_all('/<dd class=\"door-card__dd \">(.*?)<\/dd>/s', $product, $matches_values);
                $meta_values = array();
                foreach($matches_fields[1] as $i=>$v){
                    $meta_values[str_replace(":","",trim($v))] = trim($matches_values[1][$i]);
                }
                update_post_meta($pid,"meta_values",$meta_values);

                // get product gallery images
                preg_match_all('/<img src="(.*?)"/s', $product, $matches_gallery);
                update_post_meta($pid,"meta_gallery",$matches_gallery[1]);

                preg_match_all('/href="(.*?)"/s', $product, $matches_links);
                $the_link = "https://www.overheaddoor.com".trim($matches_links[1][0]);

                // update product page URL
                update_post_meta($pid,"the_link",$the_link);

                // get & save product page content
                if(!get_post_meta($pid,"the_details_page",true)){
                    $details_page = $this->import_curl_page($the_link);
                    update_post_meta($pid,"the_details_page",$details_page);
                }
            }
        }
    }


    function import_all_data(){
        // go through all the products
        $args = array("post_type"=>"product","posts_per_page"=>99999999);
        $the_query = new WP_Query( $args );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $pid = get_the_ID();
                // check if data is already imported
				if(!get_post_meta($pid,"imported_data",true)){
                    // import data
                	$this->import_details_data($pid);
                    // flag product as imported
					update_post_meta($pid,"imported_data",1);
				}
            }
        }
    }

    function import_all_subtitles(){
        // go through all the products
        $args = array("post_type"=>"product","posts_per_page"=>99999999);
        $the_query = new WP_Query( $args );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $pid = get_the_ID();
                // import the product page subtitle
                $this->import_details_subtitle($pid);
            }
        }
    }

    function check_get_file($url,$pid){
        global $wpdb;

        // check if an image from that URL has been imported into WP and return attribute ID
        if($att_id = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='the_url' AND `meta_value`='".$url."'")){
            return $att_id;
        }else{
            // if the image had not been imported, import and return attribute ID
            $att_id = media_sideload_image( $url, $post_id, NULL, 'id' );
            update_post_meta($att_id,"the_url",$url);
            return $att_id;
        }

    }

    function import_details_subtitle($pid=0){
        if(!$pid){
            $pid = $_GET["pid"];
        }
        // get product link & content
        $the_link = get_post_meta($pid,"the_link",true);
        $the_page_content = get_post_meta($pid,"the_details_page",true);

        // clean page content
        $the_page_content = str_replace("\t"," ",str_replace("\n","",str_replace("\r","",$the_page_content)));
        for($i=0;$i<10;$i++){
            $the_page_content = str_replace("  "," ",$the_page_content);
        }
        $the_page_content = str_replace("</div> </div>","</div></div>",$the_page_content);

        // get product subtitle
        preg_match('/<span class="h5 product-detail__sub-title">(.*?)<\/span>/s', $the_page_content, $matches);
        // save product subtitle
        update_post_meta($pid,"page_subtitle",$matches[1]);        
    }

    function import_details_data($pid=0){
        if(!$pid){
            $pid = $_GET["pid"];
        }

        // get product link & content
        $the_link = get_post_meta($pid,"the_link",true);
        // clean page content
        $the_page_content = get_post_meta($pid,"the_details_page",true);
        $the_page_content = str_replace("\t"," ",str_replace("\n","",str_replace("\r","",$the_page_content)));
        for($i=0;$i<10;$i++){
            $the_page_content = str_replace("  "," ",$the_page_content);
        }
        $the_page_content = str_replace("</div> </div>","</div></div>",$the_page_content);

        // import product content
        preg_match('/<div class=\" product-detail__content\">(.*?)<\/div>/s', $the_page_content, $matches);
        $my_post = array(
            'ID'             => $pid,
            'post_content'   => ($matches[1]),
        );
        wp_update_post( $my_post );


        //import windows section content
        preg_match('/<div id=\"Windows(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"windows",$matches[0]);
        }
        //import window placement section content
        preg_match('/<div id=\"Window Placements(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"window_placements",$matches[0]);
        }
        //import glass section content
        preg_match('/<div id=\"Glass(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"glass",$matches[0]);
        }
        //import colors section content
        preg_match('/<div id=\"Color(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"colors",$matches[0]);
        }
        //import panel_designs section content
        preg_match('/<div id=\"Panel Designs(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"panel_designs",$matches[0]);
        }
        //import panel_options section content
        preg_match('/<div id=\"Panel Options(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"panel_options",$matches[0]);
        }
        //import frames section content
        preg_match('/<div id=\"Frames(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"frames",$matches[0]);
        }
        //import enhancements section content
        preg_match('/<div id=\"enhancements(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"enhancements",$matches[0]);
        }
        //import hardware section content
        preg_match('/<div id=\"Hardware(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"hardware",$matches[0]);
        }

        //import specification section content
		preg_match('/<div class="product-detail__features"(.*?)<\/div><\/div>/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"specification_content",$matches[0]);
        }
        preg_match('/<div class="product-detail__features"(.*?)<\/div><\/div> <div/s', $the_page_content, $matches);
        if($matches[0]){
            update_post_meta($pid,"specification_content",substr($matches[0],0,strlen($matches[0])-4));
        }

        //import the resources section content
        preg_match('/<div class="product-detail__tab-panel product-detail__tab-panel--docs" role="tabpanel" tabindex="0">(.*?)<\/div><\/div><\/div>/s', $the_page_content, $matches);
        $resources = ($matches[1]);

        preg_match_all('/<h3 class="product-detail__accordion-title">(.*?)<\/div><\/div>/s', $resources, $matches);
        $resources = 0;

        foreach($matches[0] as $res_1){
            // create 1st level resources
            preg_match('/<h3 class="product-detail__accordion-title">(.*?)<\/h3>/s', $res_1, $matches);
            $prod_name = trim(strip_tags($matches[1]));
            $resource_categories = 0;

            preg_match_all('/<div class="product-detail__doc-group">(.*?)<\/div>/s', $res_1, $matches);
            foreach($matches[1] as $res_2){
                // create 2nd level resources
                preg_match('/<h4 class="product-detail__doc-group-title">(.*?)<\/h4>/s', $res_2, $matches);
                $category_name = trim(strip_tags($matches[1]));
                
                $resource_files = 0;
                preg_match_all('/<li class="document-item(.*?)<\/li>/s', $res_2, $matches);
                foreach($matches[0] as $res_3){
                    // create 3rd level resources
                    preg_match('/<a href="(.*?)"/s', $res_3, $matches);
                    $link = ($matches[1]);
                    preg_match('/<a href="(.*?)<\/a>/s', $res_3, $matches);
                    $label = trim(str_replace("(opens in a new window)","",strip_tags($matches[0])));

                    update_post_meta($pid,"resources_".$resources."_resource_categories_".$resource_categories."_files_".$resource_files."_name",$label);
                    update_post_meta($pid,"resources_".$resources."_resource_categories_".$resource_categories."_files_".$resource_files."_file_url",$link);
                    $resource_files++;
                }
                update_post_meta($pid,"resources_".$resources."_resource_categories_".$resource_categories."_category_name",$category_name);
                update_post_meta($pid,"resources_".$resources."_resource_categories_".$resource_categories."_files",$resource_files);
                $resource_categories++;
            }
            update_post_meta($pid,"resources_".$resources."_product",$prod_name);
            update_post_meta($pid,"resources_".$resources."_resource_categories",$resource_categories);
            $resources++;
        }
        // update ACF resources repeater field
        update_post_meta($pid,"resources",$resources);

        // process brochures
        preg_match_all('/<li class="brochure-listing__item">(.*?)<\/li>/s', $the_page_content, $matches);
        if($matches[0]){
            $i=0;
            foreach($matches[1] as $brochure){
                // get brochure image
                preg_match('/<img src="(.*?)"/s', $brochure, $matches);
                $url = trim($matches[1]);
                $url_pieces = explode("?",$url);
                $brochure_image = $url_pieces[0];
                
                // get brochure url
                preg_match('/<a href="(.*?)"/s', $brochure, $matches);
                $url = trim($matches[1]);
                $url_pieces = explode("?",$url);
                $brochure_link = $url_pieces[0];

                // get brochure name
                preg_match('/<h3 class=\"brochure-listing__title\">(.*?)<\/h3>/s', $brochure, $matches);
                $brochure_name = trim(strip_tags($matches[1]));

                // import brochure image
                $att_id = $this->check_get_file($brochure_image,$pid);
                update_post_meta($pid,"brochures_".$i."_image",$att_id);
                update_post_meta($pid,"brochures_".$i."_name",$brochure_name);
                update_post_meta($pid,"brochures_".$i."_file_url",$brochure_link);
                $i++;
            }
            // update ACF brochures repeater field
            update_post_meta($pid,"brochures",$i);
        }

        // process gallery images
        preg_match_all('/<figure class=\"product-media-gallery__figure \">(.*?)<\/figure>/s', $the_page_content, $matches);
        if($matches[0]){
            $i=0;
            $gallery = array();
            foreach($matches[1] as $gal){
                preg_match('/<img src="(.*?)"/s', $gal, $matches);
                $url = trim($matches[1]);
                $url_pieces = explode("?",$url);
                $gal_image = $url_pieces[0];

                // import images
                $att_id = $this->check_get_file($gal_image,$pid);
                // if no main image, make first image featured image
                if(!get_post_meta($pid,"_thumbnail_id",true)){
                    update_post_meta($pid,"_thumbnail_id",$att_id);
                }
                $gallery[]=$att_id;
            }
            // update ACF gallery field
            update_post_meta($pid,"image_gallery",$gallery);
        }

        // get applications terms 
        preg_match_all('/<h3 class=\"h5 product-pages__title\">(.*?)<\/h3>/s', $the_page_content, $matches);
        if($matches[0]){
            $applications = array();
            foreach($matches[1] as $v){
                $applications[] = trim(strip_tags($v));
            }
            wp_set_object_terms($pid, $applications, "application", 1);
        }
    }

    function import_curl_page($url){
        $ch = curl_init("http://95.76.134.184:8191/v1");
        // Setup request to send json via POST.{
        $payload = '{"cmd": "request.get","url": "'.$url.'","maxTimeout": 60000}';
        print_r($payload);
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        // Return response instead of printing.
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        // Send request.
        $result = curl_exec($ch);
        $arr = (json_decode($result));

        curl_close($ch);
        // Print response.
        return $arr->solution->response;
    }


    function import_residential_doors(){
        global $wpdb;
        // process residential page list HTML
        $residential_content = file_get_contents(__DIR__."/importdoors/residential.php");
        $residential_content = str_replace("\t"," ",str_replace("\n","",str_replace("\r","",$residential_content)));

        // clean up HTML code
        for($i=0;$i<10;$i++){
            $residential_content = str_replace("  "," ",$residential_content);
        }

        // go through all the products
        preg_match_all('/<article class=\"door-card\">(.*?)<\/article>/s', $residential_content, $matches);
        $products = ($matches[1]);
        foreach($products as $product){
            // get product name
            preg_match('/<h3 class=\"h5 door-card__title\">(.*?)<\/h3>/s', $product, $matches);
            $the_title = trim($matches[1]);
            // get product subtitle
            preg_match('/<h4 class=\"door-card__sub-heading\">(.*?)<\/h4>/s', $product, $matches);
            $the_subtitle = trim($matches[1]);
           // create identifier
           $identifier = $the_title."-".$the_subtitle;

           // check if product exists
           if($pid = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='prod_identifier' AND `meta_value`='".$identifier."'")){
               // if product exists, update post
               $my_post = array(
                   'ID'            => $pid,
                   'post_title'    => wp_strip_all_tags($the_title),
                   'post_status'   => 'publish',
                   'post_type'     => 'product',
               );
                   
               wp_update_post( $my_post );
           }else{
               // if product does not exist, create the post
               $my_post = array(
                   'post_title'    => wp_strip_all_tags($the_title),
                   'post_status'   => 'publish',
                   'post_type'     => 'product',
               );
                   
               $pid = wp_insert_post( $my_post );
               update_post_meta($pid,"prod_identifier",$identifier);
           }
           // update subtitle
           update_post_meta($pid,"subtitle",$the_subtitle);
           // set product category
           wp_set_object_terms($pid, $the_term->term_id, "product-category", 1);

           // get product link
           preg_match_all('/href="(.*?)"/s', $product, $matches_links);
           $the_link = "https://www.overheaddoor.com".trim($matches_links[1][0]);
           update_post_meta($pid,"the_link",$the_link);

           // set product category
            wp_set_object_terms($pid, "All Residential Garage Doors", "product-category", 1);

            // get attribute table values
            preg_match_all('/<dt class=\"door-card__dt\ ">(.*?)<\/dt>/s', $product, $matches_fields);
            preg_match_all('/<dd class=\"door-card__dd \">(.*?)<\/dd>/s', $product, $matches_values);
            $meta_values = array();
            foreach($matches_fields[1] as $i=>$v){
               $meta_values[str_replace(":","",trim($v))] = trim($matches_values[1][$i]);
            }
            update_post_meta($pid,"meta_values",$meta_values);

            // get product gallery images
            preg_match_all('/<img src="(.*?)"/s', $product, $matches_gallery);
            update_post_meta($pid,"meta_gallery",$matches_gallery[1]);

            // get & save product page content
            if(!get_post_meta($pid,"the_details_page",true)){
                $details_page = $this->import_curl_page($the_link);
                update_post_meta($pid,"the_details_page",$details_page);
            }
        }
    }

    
    function import_commercial_doors(){
        global $wpdb;

        // process commercial page list HTML
        $residential_content = file_get_contents(__DIR__."/importdoors/commercial.php");
        $residential_content = str_replace("\t"," ",str_replace("\n","",str_replace("\r","",$residential_content)));

        // clean up HTML code
        for($i=0;$i<10;$i++){
            $residential_content = str_replace("  "," ",$residential_content);
        }

       // go through all the products
       preg_match_all('/<article class=\"door-card\">(.*?)<\/article>/s', $residential_content, $matches);
       $products = ($matches[1]);
       foreach($products as $product){
           // get product name
           preg_match('/<h3 class=\"h5 door-card__title\">(.*?)<\/h3>/s', $product, $matches);
           $the_title = trim($matches[1]);
           // get product subtitle
           preg_match('/<h4 class=\"door-card__sub-heading\">(.*?)<\/h4>/s', $product, $matches);
           $the_subtitle = trim($matches[1]);
          // create identifier
          $identifier = $the_title."-".$the_subtitle;

          // check if product exists
          if($pid = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='prod_identifier' AND `meta_value`='".$identifier."'")){
              // if product exists, update post
              $my_post = array(
                  'ID'            => $pid,
                  'post_title'    => wp_strip_all_tags($the_title),
                  'post_status'   => 'publish',
                  'post_type'     => 'product',
              );
                  
              wp_update_post( $my_post );
          }else{
              // if product does not exist, create the post
              $my_post = array(
                  'post_title'    => wp_strip_all_tags($the_title),
                  'post_status'   => 'publish',
                  'post_type'     => 'product',
              );
                  
              $pid = wp_insert_post( $my_post );
              update_post_meta($pid,"prod_identifier",$identifier);
          }
          // update subtitle
          update_post_meta($pid,"subtitle",$the_subtitle);
          // set product category
          wp_set_object_terms($pid, $the_term->term_id, "product-category", 1);

          // get product link
          preg_match_all('/href="(.*?)"/s', $product, $matches_links);
          $the_link = "https://www.overheaddoor.com".trim($matches_links[1][0]);
          update_post_meta($pid,"the_link",$the_link);

          // set product category
           wp_set_object_terms($pid, "All Residential Garage Doors", "product-category", 1);

           // get attribute table values
           preg_match_all('/<dt class=\"door-card__dt\ ">(.*?)<\/dt>/s', $product, $matches_fields);
           preg_match_all('/<dd class=\"door-card__dd \">(.*?)<\/dd>/s', $product, $matches_values);
           $meta_values = array();
           foreach($matches_fields[1] as $i=>$v){
              $meta_values[str_replace(":","",trim($v))] = trim($matches_values[1][$i]);
           }
           update_post_meta($pid,"meta_values",$meta_values);

           // get product gallery images
           preg_match_all('/<img src="(.*?)"/s', $product, $matches_gallery);
           update_post_meta($pid,"meta_gallery",$matches_gallery[1]);

           // get & save product page content
           if(!get_post_meta($pid,"the_details_page",true)){
               $details_page = $this->import_curl_page($the_link);
               update_post_meta($pid,"the_details_page",$details_page);
           }
       }// go through all the products
       preg_match_all('/<article class=\"door-card\">(.*?)<\/article>/s', $residential_content, $matches);
       $products = ($matches[1]);
       foreach($products as $product){
           // get product name
           preg_match('/<h3 class=\"h5 door-card__title\">(.*?)<\/h3>/s', $product, $matches);
           $the_title = trim($matches[1]);
           // get product subtitle
           preg_match('/<h4 class=\"door-card__sub-heading\">(.*?)<\/h4>/s', $product, $matches);
           $the_subtitle = trim($matches[1]);
          // create identifier
          $identifier = $the_title."-".$the_subtitle;

          // check if product exists
          if($pid = $wpdb->get_var("SELECT `post_id` FROM `".$wpdb->postmeta."` WHERE `meta_key`='prod_identifier' AND `meta_value`='".$identifier."'")){
              // if product exists, update post
              $my_post = array(
                  'ID'            => $pid,
                  'post_title'    => wp_strip_all_tags($the_title),
                  'post_status'   => 'publish',
                  'post_type'     => 'product',
              );
                  
              wp_update_post( $my_post );
          }else{
              // if product does not exist, create the post
              $my_post = array(
                  'post_title'    => wp_strip_all_tags($the_title),
                  'post_status'   => 'publish',
                  'post_type'     => 'product',
              );
                  
              $pid = wp_insert_post( $my_post );
              update_post_meta($pid,"prod_identifier",$identifier);
          }
          // update subtitle
          update_post_meta($pid,"subtitle",$the_subtitle);
          // set product category
          wp_set_object_terms($pid, $the_term->term_id, "product-category", 1);

          // get product link
          preg_match_all('/href="(.*?)"/s', $product, $matches_links);
          $the_link = "https://www.overheaddoor.com".trim($matches_links[1][0]);
          update_post_meta($pid,"the_link",$the_link);

          // set product category
          wp_set_object_terms($pid, "All Commercial Doors", "product-category", 1);

           // get attribute table values
           preg_match_all('/<dt class=\"door-card__dt\ ">(.*?)<\/dt>/s', $product, $matches_fields);
           preg_match_all('/<dd class=\"door-card__dd \">(.*?)<\/dd>/s', $product, $matches_values);
           $meta_values = array();
           foreach($matches_fields[1] as $i=>$v){
              $meta_values[str_replace(":","",trim($v))] = trim($matches_values[1][$i]);
           }
           update_post_meta($pid,"meta_values",$meta_values);

           // get product gallery images
           preg_match_all('/<img src="(.*?)"/s', $product, $matches_gallery);
           update_post_meta($pid,"meta_gallery",$matches_gallery[1]);

           // get & save product page content
           if(!get_post_meta($pid,"the_details_page",true)){
               $details_page = $this->import_curl_page($the_link);
               update_post_meta($pid,"the_details_page",$details_page);
           }

        }
    }
}

new import_door_data;
