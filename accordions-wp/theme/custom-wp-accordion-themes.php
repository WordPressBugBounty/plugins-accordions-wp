<?php
    if( !defined( 'ABSPATH' ) ){
        exit;
    }

	function TCP_accordions_wordpress_table_body($postid){
		$tcpfeaturess 							= get_post_meta( $postid, 'custom_accordion_wordpresspro_columns');
		$custom_accordion_columns_post_themes 	= get_post_meta( $postid, 'custom_accordion_columns_post_themes', true );
		$custom_accordion_title_bg_color 		= get_post_meta( $postid, 'custom_accordion_title_bg_color', true );
		$custom_accordion_title_font_color 		= get_post_meta( $postid, 'custom_accordion_title_font_color', true );
		$custom_accordion_title_font_size 		= get_post_meta( $postid, 'custom_accordion_title_font_size', true );
		$custom_accordion_content_bg_color 		= get_post_meta( $postid, 'custom_accordion_content_bg_color', true );
		$custom_accordion_content_font_color 	= get_post_meta( $postid, 'custom_accordion_content_font_color', true );
		$custom_accordion_content_font_size 	= get_post_meta( $postid, 'custom_accordion_content_font_size', true );
		$custom_accordion_content_padding 		= get_post_meta( $postid, 'custom_accordion_content_padding', true );
		
		if($custom_accordion_columns_post_themes=="theme1"){
			$logotesting ='';
				$logotesting.='<div class="container '.esc_attr( $custom_accordion_columns_post_themes ).'" style="width:100%; height:auto">';
				$logotesting.='<ul class="responsive-accordion responsive-accordion-default bm-larger">';
					foreach ($tcpfeaturess as $tcpfeature) {
						$logotesting.='<li>';
						$logotesting.='<div class="responsive-accordion-head" style="background-color:'.esc_attr( $custom_accordion_title_bg_color ).'">';
						$logotesting.='<span style="color:'.esc_attr( $custom_accordion_title_font_color ).';font-size:'.esc_attr( $custom_accordion_title_font_size ).'px">'.esc_attr( $tcpfeature['custom_accordions_pro_title'] ).'</span>';
						$logotesting.='<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
						$logotesting.='</div>';
						$logotesting.='<div class="responsive-accordion-panel"style="background-color:'.esc_attr( $custom_accordion_content_bg_color ).';padding:'.$custom_accordion_content_padding.'px;color:'.esc_attr( $custom_accordion_content_font_color ).';font-size:'.esc_attr( $custom_accordion_content_font_size ).'px">';
						$logotesting.=''.wpautop( do_shortcode( $tcpfeature['custom_accordions_pro_details'] ) ).'';
						$logotesting.='</div>';
						$logotesting.='</li>';
					};
				$logotesting.='</ul>';
				$logotesting.='</div>';
			return $logotesting;
		}		
		if($custom_accordion_columns_post_themes=="theme2"){
			$logotesting ='';
				$logotesting.='<div class="container '.esc_attr( $custom_accordion_columns_post_themes ).'" style="width:100%; height:auto">';
				$logotesting.='<ul class="responsive-accordion responsive-accordion-default bm-larger">';
					foreach ($tcpfeaturess as $tcpfeature) {
						$logotesting.='<li>';
						$logotesting.='<div class="responsive-accordion-head" style="background-color:'.esc_attr( $custom_accordion_title_bg_color ).'">';
						$logotesting.='<span style="color:'.esc_attr( $custom_accordion_title_font_color ).';font-size:'.esc_attr( $custom_accordion_title_font_size ).'px">'.esc_attr( $tcpfeature['custom_accordions_pro_title'] ).'</span>';
						$logotesting.='<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
						$logotesting.='</div>';
						$logotesting.='<div class="responsive-accordion-panel"style="background-color:'.esc_attr( $custom_accordion_content_bg_color ).';padding:'.esc_attr( $custom_accordion_content_padding ).'px;color:'.esc_attr( $custom_accordion_content_font_color ).';font-size:'.esc_attr( $custom_accordion_content_font_size ).'px">';
						$logotesting.=''.wpautop( do_shortcode( $tcpfeature['custom_accordions_pro_details'] ) ).'';
						$logotesting.='</div>';
						$logotesting.='</li>';
					};
				$logotesting.='</ul>';
				$logotesting.='</div>';
			return $logotesting;
		}		
		if($custom_accordion_columns_post_themes=="theme3"){
			$logotesting ='';
				$logotesting.='<div class="container '.esc_attr( $custom_accordion_columns_post_themes ).'" style="width:100%; height:auto">';
				$logotesting.='<ul class="responsive-accordion responsive-accordion-default bm-larger">';

				foreach ($tcpfeaturess as $tcpfeature) {
					$logotesting.='<li>';
					$logotesting.='<div class="responsive-accordion-head" style="background-color:'.esc_attr( $custom_accordion_title_bg_color ).'">';
					$logotesting.='<span style="color:'.esc_attr( $custom_accordion_title_font_color ).';font-size:'.esc_attr( $custom_accordion_title_font_size ).'px">'.esc_attr( $tcpfeature['custom_accordions_pro_title'] ).'</span>';
					$logotesting.='<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
					$logotesting.='</div>';
					$logotesting.='<div class="responsive-accordion-panel"style="background-color:'.esc_attr( $custom_accordion_content_bg_color ).';padding:'.esc_attr( $custom_accordion_content_padding ).'px;color:'.esc_attr( $custom_accordion_content_font_color ).';font-size:'.esc_attr( $custom_accordion_content_font_size ).'px">';
					$logotesting.=''.wpautop( do_shortcode( $tcpfeature['custom_accordions_pro_details'] ) ).'';
					$logotesting.='</div>';
					$logotesting.='</li>';
				};
				$logotesting.='</ul>';
				$logotesting.='</div>';
			return $logotesting;
		}
		if($custom_accordion_columns_post_themes=="theme4"){
			$logotesting ='';
				$logotesting.='<div class="container '.esc_attr( $custom_accordion_columns_post_themes ).'" style="width:100%; height:auto">';
				$logotesting.='<ul class="responsive-accordion responsive-accordion-default bm-larger">';

				foreach ($tcpfeaturess as $tcpfeature) {
					$logotesting.='<li>';
					$logotesting.='<div class="responsive-accordion-head" style="background-color:'.esc_attr( $custom_accordion_title_bg_color ).'">';
					$logotesting.='<span style="color:'.esc_attr( $custom_accordion_title_font_color ).';font-size:'.esc_attr( $custom_accordion_title_font_size ).'px">'.esc_attr( $tcpfeature['custom_accordions_pro_title'] ).'</span>';
					$logotesting.='<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
					$logotesting.='</div>';
					$logotesting.='<div class="responsive-accordion-panel"style="background-color:'.esc_attr( $custom_accordion_content_bg_color ).';padding:'.esc_attr( $custom_accordion_content_padding ).'px;color:'.esc_attr( $custom_accordion_content_font_color ).';font-size:'.esc_attr( $custom_accordion_content_font_size ).'px">';
					$logotesting.=''.wpautop( do_shortcode( $tcpfeature['custom_accordions_pro_details'] ) ).'';
					$logotesting.='</div>';
					$logotesting.='</li>';
				};
				$logotesting.='</ul>';
				$logotesting.='</div>';
			return $logotesting;
		}
		if($custom_accordion_columns_post_themes=="theme5"){
			$logotesting ='';
				$logotesting.='<div class="container '.esc_attr( $custom_accordion_columns_post_themes ).'" style="width:100%; height:auto">';
				$logotesting.='<ul class="responsive-accordion responsive-accordion-default bm-larger">';
				foreach ($tcpfeaturess as $tcpfeature) {
					$logotesting.='<li>';
					$logotesting.='<div class="responsive-accordion-head" style="background-color:'.esc_attr( $custom_accordion_title_bg_color ).'">';
					$logotesting.='<span style="color:'.esc_attr( $custom_accordion_title_font_color ).';font-size:'.esc_attr( $custom_accordion_title_font_size ).'px">'.esc_attr( $tcpfeature['custom_accordions_pro_title'] ).'</span>';
					$logotesting.='<i class="fa fa-chevron-down responsive-accordion-plus fa-fw"></i><i class="fa fa-chevron-up responsive-accordion-minus fa-fw"></i>';
					$logotesting.='</div>';
					$logotesting.='<div class="responsive-accordion-panel"style="background-color:'.esc_attr( $custom_accordion_content_bg_color ).';padding:'.esc_attr( $custom_accordion_content_padding ).'px;color:'.esc_attr( $custom_accordion_content_font_color ).';font-size:'.esc_attr( $custom_accordion_content_font_size ).'px">';
					$logotesting.=''.wpautop( do_shortcode( $tcpfeature['custom_accordions_pro_details'] ) ).'';
					$logotesting.='</div>';
					$logotesting.='</li>';
				};
				$logotesting.='</ul>';
				$logotesting.='</div>';
			return $logotesting;
		}
		else{
			echo 'Nothing Found!!';
		}
	}

?>