<!-- footer.php -->







<?php /* opened in assets/templates/header_body.php */ ?>



	

</div><!-- /content_container -->





	

	<div id="footer">

		<div id="wrapper">

			<div id="data_footer">

	

			</div>	<!-- /data_footer -->

		</div>

		

	</div><!-- /footer -->



	<?php

						echo "<div class='slider_footer'>";

						// are we using the page footer widget?

						if ( ! dynamic_sidebar( 'cp-license-8' ) ) {



							// no - make other provision here cp-license-8



							// compat with wplicense plugin

							if ( function_exists( 'isLicensed' ) AND isLicensed() ) {



								// show the license from wpLicense

								cc_showLicenseHtml();



							} else {



								// show copyright

								?><p><?php _e( 'Website content', 'commentpress-core' ); ?> &copy; <a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a> <?php echo date('Y'); ?>. <?php _e( 'All rights reserved.', 'commentpress-core' ); ?></p><?php

						

							}



						}

						echo "</div>";

						?>

	

</div><!-- /container -->







<?php wp_footer() ?>







</body>







</html>

