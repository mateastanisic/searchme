
<div class="right" id="add_new">

    <!-- ako smo u prethodnom slučaju dodali neki film treba se ispisti poruka o usješnom dodavanju filma u bazu -->
    <!--ispiši odgovarajuću poruku-->
    <div class="message_on_right"> 	<?php if( isset($message) && strlen($message) ) echo $message; ?> </div>

    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/add_new_movie" enctype="multipart/form-data" >
        Title:
        <input type="text" name="new_title" class="nice_input"/>
        <br />

        Categories:
        <input type="text" name="new_category" class="nice_input"/>
        <br />

        Summary:
        <input type="text" name="new_summary" class="nice_input"/>
        <br />

        Description:
        <input type="text" name="new_description" class="nice_input"/>
        <br />

        <button type="submit" >Add movie</button> <br /><br />
    </form>

</div>
