<?php

 global $post;

 global $wpdb;

 $new_notes_page = get_post_meta($post->ID, '_wp_editor_test_1', true);

 $new_vocab_page= get_post_meta($post->ID, '_wp_editor_test_2', true);

 //$new_notes_post = get_post_meta($post->ID, '_wp_editor_test_3', true);

 //$new_vocab_post = get_post_meta($post->ID, '_wp_editor_test_4', true);

  if($new_notes_page==false OR $new_vocab_page==false)

 { 

	   echo '<style type="text/css">

		 #wrapper

		 {  

		  width: 84%; 

		 }

	     </style>';

 }     

                         

 else

 {

?>      

<div class="tab_one">

  <button class="tablinks" onclick="openCity(event, 'Notes')" id="defaultOpen">Notes</button>

  <button class="tablinks" onclick="openCity(event, 'Vocabulary')">Vocabulary</button>

</div>

<div id="Notes" class="tabcontent active">

  <?php 

  global $post;

  global $wpdb;

  $key_1_value_note = get_post_meta($post->ID, '_wp_editor_test_1', true);

  //$key_2_value_note = get_post_meta($post->ID, '_wp_editor_test_3', true);

  if ($key_1_value_note==true OR $key_2_value_note==true)

	{	

	echo wpautop($key_1_value_note);

	//echo wpautop($key_2_value_note);

	}	

               

  ?>	 

</div>

<div id="Vocabulary" class="tabcontent sen_serif_font"> 

  <?php 		

  global $post;	

  global $wpdb;

  $key_1_value_vacub = get_post_meta($post->ID, '_wp_editor_test_2', true); 

  //$key_2_value_vacub = get_post_meta($post->ID, '_wp_editor_test_4', true);	

  if ($key_1_value_vacub==true OR $key_2_value_vacub==true)	

	{		

	echo wpautop($key_1_value_vacub);	

	//echo wpautop($key_2_value_vacub);

	}		

 	   

      

  ?>

</div>



<?php	

	 

	 

 }

 ?>



 

 <?php

 global $post;

 global $wpdb;

// $new_notes_page = get_post_meta($post->ID, '_wp_editor_test_1', true)=="";

//$new_vocab_page= get_post_meta($post->ID, '_wp_editor_test_2', true)=="";

$new_notes_post = get_post_meta($post->ID, '_wp_editor_test_3', true);

$new_vocab_post = get_post_meta($post->ID, '_wp_editor_test_4', true);

  if($new_notes_post==false OR $new_vocab_post==false)

 { 

	       echo '<style type="text/css">

		 .wrapper2

		 {  

		  width: 84%; 

		 }

	     </style>';   

     

 }     

 

 else

 {

?>   

 

<div class="tab_one">

  <button class="tablinks" onclick="openCity(event, 'Notes')" id="defaultOpen">Notes</button>

  <button class="tablinks" onclick="openCity(event, 'Vocabulary')">Vocabulary</button>

</div>

<div id="Notes" class="tabcontent active">

  <?php 

  global $post;

  global $wpdb;

  $key_1_value_note = get_post_meta($post->ID, '_wp_editor_test_1', true);

  $key_2_value_note = get_post_meta($post->ID, '_wp_editor_test_3', true);

  if ($key_1_value_note==true OR $key_2_value_note==true)

	{	

	//echo wpautop($key_1_value_note);

	echo wpautop($key_2_value_note);

	}	

               

  ?>	

</div>

<div id="Vocabulary" class="tabcontent sen_serif_font"> 

  <?php 		

  global $post;	

  global $wpdb;

  $key_1_value_vacub = get_post_meta($post->ID, '_wp_editor_test_2', true); 

  $key_2_value_vacub = get_post_meta($post->ID, '_wp_editor_test_4', true);	

  if ($key_1_value_vacub==true OR $key_2_value_vacub==true)	

	{		

	//echo wpautop($key_1_value_vacub);	

	echo wpautop($key_2_value_vacub);

	}		

 	   

      

  ?>

</div>





<?php	  

	                            

 } 

 ?>

 

 

  

  

  

  

  <script>

  function openCity(evt, cityName) 

  {  

  var i, tabcontent, tablinks;   

  tabcontent = document.getElementsByClassName("tabcontent");  

  for (i = 0; i < tabcontent.length; i++)

  {  

  tabcontent[i].style.display = "none";   

  }   

  tablinks = document.getElementsByClassName("tablinks"); 

  for (i = 0; i < tablinks.length; i++)

  {   

  tablinks[i].className = tablinks[i].className.replace(" active", "");  

  }  

  document.getElementById(cityName).style.display = "block"; 

  evt.currentTarget.className += " active";

  }

  

// Get the element with id="defaultOpen" and click on it

document.getElementById("defaultOpen").click();

  

  

  </script>

  

  

  