
<div class="right" id="add_new">

    <!-- ako smo u prethodnom slučaju dodali neki film treba se ispisti poruka o usješnom dodavanju filma u bazu -->
    <!--ispiši odgovarajuću poruku-->
    <div class="message_on_right"> 	<?php if( isset($message) && strlen($message) && $option === '1' ) echo $message; ?> </div>

    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/add_new_movie" enctype="multipart/form-data" >
        <table>
            <tr>
                <td>
                    Title:
                </td>
                <td>
                    <input type="text" name="new_title" class="nice_input"/>
                </td>
            </tr>
            <tr>
                <td>
                    Categories:
                </td>
                <td>
                    <input type="text" name="new_category" class="nice_input"/>
                </td>
            </tr>
            <tr>
                <td>
                    Summary:
                </td>
                <td>
                    <input type="text" name="new_summary" class="nice_input"/>
                </td>
            </tr>
            <tr>
                <td>
                    Description:
                </td>
                <td>
                    <input type="text" name="new_description" class="nice_input"/>
                </td>
            </tr>
        </table>

        <br /><br />
        <button type="submit" class="button_to_right" >add movie</button> <br /><br />
    </form>

</div>
