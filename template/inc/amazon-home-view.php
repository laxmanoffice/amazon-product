<?php
if(empty($atts['content_word_count']))
{
	$atts['content_word_count'] = 380;
}
if(empty($atts['inbound_button_text']))
{
	$atts['inbound_button_text'] = "Check It Out";
}
if(empty($atts['afiliate_button_text']))
{
	$atts['afiliate_button_text'] = "GET IT HERE";
}

if($atts['amazon_show_post'] == 'Show only posts'){
	$post_args = array(
		'post_type' => 'post',
		'post_status'=>'publish',
		'tax_query' => array(  
			array(  
				'taxonomy' => 'gift_category',  
				'field' => 'slug',  
				'terms' => str_replace(' ','-', strtolower($atts['gift_title'])),
			)  
		)  
	);
	$args = array(
		'p'  => $atts['gift_id'],
		'post_type' => 'gift_guide',
		'post_status'=>'publish',
	);
		
	$ip = $this->getLocationInfoByIp2();
	$countery = $ip[country];
	$post_query = new WP_Query($post_args);
	$rkpost_count = $post_query->post_count;
	//echo $rkpost_count;
	$pc =0;
	$count_n = 0;
				
	if ( $post_query->have_posts()) :
		while ( $post_query->have_posts() ) : $post_query->the_post(); 
			$post_title 	  	= get_post_meta(get_the_ID(), 'gift_post_title', true);	
			$link_us 		  	= get_post_meta(get_the_ID(), 'gift_post_link_us', true);
			$link_uk 		  	= get_post_meta(get_the_ID(), 'gift_post_link_uk', true);
			$gift_post_image 	= get_post_meta(get_the_ID(), 'gift_post_image', true);
			$post_content 		= get_post_meta(get_the_ID(), 'gift_guide_content', true);
			$inbound_link 		= get_post_meta(get_the_ID(), 'gift_post_inbound', true);
						
			if($countery == 'GB'){ //GB
				$link = $link_uk; 
			}else{ // For US
				$link = $link_us;
			}
			
			?>
				
			<div id="<?php echo str_replace(array(" ","'",",","&","_"),'-',trim(get_the_title())); ?>" class="homeview dynamic_post_home_view" data-return_product_url="<?php echo get_the_ID(); ?>" data-product_column="<?php echo $dyncol; ?>" data-button_style="<?php echo $buttonstyle; ?>" data-which_site="<?php echo $atts['amazon_button_style']; ?>" data-word_count="<?php echo $atts['content_word_count']; ?>" data-afiliate-button-text="<?php echo $atts['afiliate_button_text']; ?>" data-inbound-button-text="<?php echo $atts['inbound_button_text']; ?>">
				<div class="<?php echo $dyncol; ?> ajax_lp"  id="ajax_lp_<?php echo $count_n ?>">
		
					<div class="gift-grid__item">
						<article class="post-preview">
							<figure class="post-preview__img-wrap">
								<img width="534" height="335" class="post-preview__img" src="<?php echo $gift_post_image; ?>">
							</figure>
							<div class="post-preview__content">
								<h2 class="post-preview__title"><?php echo $post_title; ?></h2>
								<p class="post-preview__txt"><?php echo substr($post_content, 0, $atts['content_word_count']); ?></p>
                                <p class="post-preview__txt"><?php if($atts['gift_more_text']){ echo $post_content; }else{ echo substr($post_content, 0, $atts['content_word_count']); } ?></p>
							</div>
							
							<div class="post-preview__bottom">
								<div class="post-preview__bottom__col">
							</div>
							<div class="post-preview__bottom__col">
                            	<?php if($inbound_link){ ?> 
									<a href="<?php echo $inbound_link; ?>" class="btn-alt" target="_blank"><?php echo $atts['inbound_button_text']; ?></a>
								<?php }else{ ?>
									<span class="btn-alt" href="#" target="_blank"><?php echo $atts['afiliate_button_text']; ?></span>
                                <?php } ?>
							</div>
						</article>					
					</div>
				</div>
			</div>
				
	<?php $count_n++; 
	$pc++;
	endwhile; 
	endif;
	wp_reset_query();
}

elseif($atts['amazon_show_post'] == 'Show both Gift Guide and Posts'){
	
	$gift_array = array();
	$post_args = array(
		'post_type' => 'post',
		'post_status'=>'publish',
		'tax_query' => array(  
			array(  
				'taxonomy' => 'gift_category',  
				'field' => 'slug',  
				'terms' => str_replace(' ','-', strtolower($atts['gift_title'])),
			)  
		)  
	);
	$args = array(
		'p'  => $atts['gift_id'],
		'post_type' => 'gift_guide',
		'post_status'=>'publish',
	);
		
	$ip = $this->getLocationInfoByIp2();
	$countery = $ip[country];
	$post_query = new WP_Query($post_args);
	$rkpost_count = $post_query->post_count;
	//echo $rkpost_count;
	$pc =0;
	$count_n = 0;
				
	if ( $post_query->have_posts()) :
		while ( $post_query->have_posts() ) : $post_query->the_post(); 
			$gift_array[get_the_ID()]['class']	= 'homeview dynamic_post_home_view';
		    $gift_array[get_the_ID()]['title']		= get_the_title();
			$gift_array[get_the_ID()][$count_n][]		= get_post_meta(get_the_ID(), 'gift_post_title', true);	
			$gift_array[get_the_ID()][$count_n][]		= get_post_meta(get_the_ID(), 'gift_post_link_us', true);
			$gift_array[get_the_ID()][$count_n][] 		= get_post_meta(get_the_ID(), 'gift_post_link_uk', true);
			$gift_array[get_the_ID()][$count_n][] 		= get_post_meta(get_the_ID(), 'gift_post_image', true);
			$gift_array[get_the_ID()][$count_n][] 		= get_post_meta(get_the_ID(), 'gift_guide_content', true);
			$gift_array[get_the_ID()][$count_n][] 		= get_post_meta(get_the_ID(), 'gift_post_inbound', true);

	$count_n++; 
	$pc++;
	endwhile; 
	endif;
	wp_reset_query();
	
	if($rkpost_count == $pc ){
		$amazon_query1 = new WP_Query($args);
		if ( $amazon_query1->have_posts() ) :
		
		while ( $amazon_query1->have_posts() ) : $amazon_query1->the_post();
			$count_n =  $amazon_query1->post_count;
			
			$title = explode('^',get_post_meta(get_the_ID(), 'gift_title', true));	
			$link = explode('^',get_post_meta(get_the_ID(), 'gift_link', true));
			$gift_link_uk = explode('^',get_post_meta(get_the_ID(), 'gift_link_uk', true));
			$img = explode('^',get_post_meta(get_the_ID(), 'gift_image', true));
			$description = explode('^',get_post_meta(get_the_ID(), 'gift_description', true));
			$gift_link_inbound = explode('^',get_post_meta(get_the_ID(), 'gift_link_inbound', true));
	
			for($gift=0;$gift<count($title);$gift++){
				$gift_array[get_the_ID()]['class']	= 'dynamic_gift_home_view';
				$gift_array[get_the_ID()]['title']	= get_the_title();
				$gift_array[get_the_ID()][$gift][]		= $title[$gift];
				$gift_array[get_the_ID()][$gift][]		= $link[$gift];
				$gift_array[get_the_ID()][$gift][]		=$gift_link_uk[$gift];
				$gift_array[get_the_ID()][$gift][]		= $img[$gift];
				$gift_array[get_the_ID()][$gift][]		= $description[$gift];
				$gift_array[get_the_ID()][$gift][]		= $gift_link_inbound[$gift];
			}
		endwhile; 
		endif; 
		wp_reset_query();
	}
	$post_count =0;
	$a =1;
	
	
	foreach($gift_array as $gift_key=>$gift_value){ ?>
    
            <div id="<?php echo str_replace(array(" ","'",",","&","_"),'-',trim($gift_array[$gift_key]['title'])); ?>_<?php echo $post_count; ?>" class="<?php echo $gift_array[$gift_key]['class'] ?>"  data-return_product_url="<?php echo $gift_key; ?>"  data-product_column="<?php echo $dyncol; ?>" data-button_style="<?php echo $buttonstyle; ?>" data-which_site="<?php echo $atts['amazon_button_style']; ?>" data-word_count="<?php echo $atts['content_word_count']; ?>" data-afiliate-button-text="<?php echo $atts['afiliate_button_text']; ?>" data-inbound-button-text="<?php echo $atts['inbound_button_text']; ?>" <?php if($atts['gift_more_text']){ echo 'data-more_text="1"'; ?>>
            <?php
			$gift_count = 0;
			if($atts['gift_more_text']){
				echo '<div class="vc_row">';	
			}
			foreach($gift_value as $gift_vl)
			{
				if(is_array($gift_vl))
				{ 
					if($atts['gift_more_text']){
						if($gift_count%2===0){
							echo '</div><div class="vc_row">';	
						}
					}
				?>
                
                <div class="<?php echo $dyncol; ?> ajax_lp" id="ajax_lp_<?php echo $gift_count ?>">
                    <div class="gift-grid__item">
                        <article class="post-preview">
                            <figure class="post-preview__img-wrap">
                                <img class="post-preview__img" src="<?php echo site_url(). $gift_vl[3]; ?>">
                            </figure>
                            <div class="post-preview__content">
                                <h2 class="post-preview__title"><?php echo $gift_vl[0]; ?></h2>
                                <p class="post-preview__txt"><?php if($atts['gift_more_text']){ echo $gift_vl[4]; }else{ echo substr($gift_vl[4],0, $atts['content_word_count']); } ?></p>
                            </div>
                            <div class="post-preview__bottom">
        						<div class="post-preview__bottom__col">
                            </div>
                            <div class="post-preview__bottom__col">
                                <?php if($gift_vl[5]){ ?> 
                                    <a href="<?php echo $gift_vl[5]; ?>" class="btn-alt" target="_blank"><?php echo $atts['inbound_button_text']; ?></a>
                                <?php }else{ ?>
                                    <span class="btn-alt" href="#" target="_blank"><?php echo $atts['afiliate_button_text']; ?></span>
                                <?php } ?>
                            </div>
                        </article>					
                    </div>
                </div> 
                <?php 
				
             }
			$gift_count++;
		} 
       if($atts['gift_more_text']){  echo '</div>'; } ?>
		<!--</div>-->
		</div>
		<?php
        $post_count++;
			} ?>
     </div>
   <?php
	}
}



elseif($atts['amazon_show_post'] == 'Show only Gift Guide'){
	$args = array(
		'p'  => $atts['gift_id'],
		'post_type' => 'gift_guide',
		'post_status'=>'publish',
	);
		$amazon_query1 = new WP_Query($args);
		if ( $amazon_query1->have_posts() ) :
		$post_count =0;
		while ( $amazon_query1->have_posts() ) : $amazon_query1->the_post();
			$count_n =  $amazon_query1->post_count;
			
			$title = explode('^',get_post_meta(get_the_ID(), 'gift_title', true));
			$link = explode('^',get_post_meta(get_the_ID(), 'gift_link', true));
			$gift_link_uk = explode('^',get_post_meta(get_the_ID(), 'gift_link_uk', true));
			$img = explode('^',get_post_meta(get_the_ID(), 'gift_image', true));
			$description = explode('^',get_post_meta(get_the_ID(), 'gift_description', true));
			$gift_link_inbound = explode('^',get_post_meta(get_the_ID(), 'gift_link_inbound', true));
		?>
		<div id="<?php echo str_replace(array(" ","'",",","&","_"),'-',trim(get_the_title())); ?>_<?php echo $post_count; ?>" class="dynamic_gift_home_view"  data-return_product_url="<?php echo get_the_ID(); ?>"  data-product_column="<?php echo $dyncol; ?>" data-button_style="<?php echo $buttonstyle; ?>" data-which_site="<?php echo $atts['amazon_button_style']; ?>" data-word_count="<?php echo $atts['content_word_count']; ?>" data-afiliate-button-text="<?php echo $atts['afiliate_button_text']; ?>" data-inbound-button-text="<?php echo $atts['inbound_button_text']; ?>">
			<?php
			$gift_array = array();
			for($gift=0;$gift<count($title);$gift++){
				$gift_array[$gift][]=$title[$gift];
				$gift_array[$gift][]=$link[$gift];
				$gift_array[$gift][]=$gift_link_uk[$gift];
				$gift_array[$gift][]=$img[$gift];
				$gift_array[$gift][]=$description[$gift];
				$gift_array[$gift][]=$gift_link_inbound[$gift];
			}
			$count=0;
		
		foreach($gift_array as $gift) { ?>
		<div class="<?php echo $dyncol; ?> ajax_lp" id="ajax_lp_<?php echo $count ?>">
			<div class="gift-grid__item">
				<article class="post-preview">
					<figure class="post-preview__img-wrap">
						<img class="post-preview__img" src="<?php echo site_url(). $gift[2]; ?>">
					</figure>
					<div class="post-preview__content">
						<h2 class="post-preview__title"><?php echo $gift[0]; ?></h2>
                        <p class="post-preview__txt"><?php if($atts['gift_more_text']){ echo $gift[3]; }else{ echo substr($gift[3],0, $atts['content_word_count']); } ?></p>
					</div>
					<div class="post-preview__bottom">
						<div class="post-preview__bottom__col">
					</div>
					<div class="post-preview__bottom__col">
						<?php if($gift[4]){ ?> 
							<a href="<?php echo $$gift[4]; ?>" class="btn-alt" target="_blank"><?php echo $atts['inbound_button_text']; ?></a>
						<?php }else{ ?>
							<span class="btn-alt" href="#" target="_blank"><?php echo $atts['afiliate_button_text']; ?></span>
                        <?php } ?>
					</div>
				</article>					
			</div>
		</div>
		<?php $count++;} ?>
		<!--</div>-->
		</div>
		<?php
		$post_count++;
		endwhile; 
		endif; 
		//wp_reset_postdata();
		wp_reset_query();
}?>
<a href="javascript:void(0)" style="display:none;" class="load_more_paginate alt-btn">loadMore</a>
<style>
h2.post-preview__title{
	min-height:70px;
}
.gift-grid__item .post-preview{ 
	min-height: 710px;
	max-height: 710px;
	margin-bottom: 30px;
	-webkit-transition: background ease .25s;
    -ms-transition: background ease .25s;
    transition: background ease .25s;
}
.gift-grid__item .post-preview:hover {
    background: #f2f2f2;
}
.gift-grid__item .post-preview__img-wrap img{
	max-width:100%;
}
.gift-grid__item .post-preview__title {
    font-size: 25px;
}
.gift-grid__item .btn-alt {
    padding: 15px 20px;
	font-size: 17px;
}
@media screen and (max-width: 991px){
	.gift-grid__item .btn-alt {
		padding: 15px 10px;
		font-size: 17px;
	}
} 
@media screen and (min-width: 320px) and (max-width: 420px){
	.gift-grid__item .btn-alt {
	    width: 160px;
	}
}
.gift-grid__item .no_bullets{
	display:none !important;
}
</style>

<?php if($atts['enable_auto_load']){ ?>

<style>
.ajax_lp{display:block;}
.ajax_lp:nth-of-type(1n+11) {display: none;}
</style>
<script type="text/javascript">
jQuery(document).ready(function ($) {
    $('.load_more_paginate').click(function () {
		var $n = $('#session_count');
        $n.val(Number($n.val())+1);
		var size_li = $(".ajax_lp").size();
		var visible = $(".ajax_lp:visible").length;
        var x;
		if(visible <= size_li) 
		{
			x = visible;
		}else
		{
			x =size_li;
		}
		x = parseInt(x)+10;
		$('.ajax_lp:lt('+x+')').css('display','block');
    });
	 

	$(window).scroll(function(e) {
		var visible = $(".ajax_lp:visible").length;
		var topofset = $('.ajax_lp:nth-child('+visible+')').attr('id');
		var stickyTop = $('#'+topofset).offset().top;
			if ($(window).scrollTop() >= stickyTop+100) {
			   $('.load_more_paginate').click()
		   }
    });
});
</script>
<input type="hidden" id="session_count" value="1">
<?php }




	