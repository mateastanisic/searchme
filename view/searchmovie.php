
<div class="right" id="search">

    <!-- search box -->
    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/search" >
        <div id="search_box" >
            <?php
            //u message se nalazi tekst po kojemu smo pretraživali filmove
            //u var option se nalazi vrijednost jesmo li pretraživali filmove s opcijom OR ili AND
            if( isset($search_input) && strlen($search_input)  && isset($and_or) && strlen($and_or) ){
                echo '<input list="datalist_movies_2" type="text" name="new_search" class="nice_input" placeholder="', $search_input ,'" style="font-style:italic" "/>';
                ?><button type="submit" class="search_button"> &#187; </button> <br />
                <datalist id="datalist_movies_2"></datalist><?php
                if( $and_or === 'OR'){
                    echo '<input type="radio" name="and_or" value="AND" > AND ';
                    echo '<input type="radio" name="and_or" value="OR" checked> OR ';
                }
                else if( $and_or === 'AND' ){
                    echo '<input type="radio" name="and_or" value="AND" checked> AND ';
                    echo '<input type="radio" name="and_or" value="OR" > OR ';
                }
                unset($search_input);
                unset($and_or);
            }
            else{
                //tek smo otvorili ovu opciju
                echo '<input list="datalist_movies" type="text" name="new_search" class="nice_input" placeholder="search" style="font-style:italic"/>';?>
                <datalist id="datalist_movies"> </datalist>
                <button type="submit" class="search_button"> &#187; </button> <br /> <?php
                echo '<input type="radio" name="and_or" value="AND" > AND ';
                echo '<input type="radio" name="and_or" value="OR" checked> OR ';
            }
            ?>
        </div>
    </form>


    <!-- ako smo u prethodnom slučaju pretraživali po nečemu ispisuje se rezultat pretraživanja -->
    <?php
        if( isset($query)  && isset($movies) && strlen($query) ){
            //dobili smo neki ispis
            //traba ispisati rezultat pretraživanja
            echo '<h2> search result:</h2>';


            echo 'Query string: ';?>
            <pre lang="SQL"><code>
                <?php echo $query; ?>
            </code></pre> <br /><?php

            if( is_array($movies) ){
                echo '<p> Number of movies found: <b>', count($movies),'</b></p>';
                echo '<table id="searchme_table">';
                echo '<tr><td> MOVIE NAME &nbsp;</td> <td> MOVIE DESCRIPTION </td> <td> RANK </td></tr>';
                foreach($movies as $i=>$movie){
                    echo '<tr>';
                    echo '<td>', $movie->title, '&nbsp;</td>';
                    echo '<td>', $movie->description, '&nbsp;</td>';
                    echo '<td class="oboji">', $movie->rank, '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            else echo '<p>' . $movies . '</p>'; //no movies found

            unset($query);
            unset($movies);
        }
        else if( isset($message) && strlen($message) && $option === '2' ) {
            //nismo ništa upisali u search box
            echo '<p>', $message, '</p>';
            unset($message);
        }

    ?>

</div>

<!-- AUTOCOMPLITION -->
<script>
    $( document ).ready( function(event)
    {
        var txt = $('input[type=text].nice_input');

        //kad netko tipka u input radi ....
        txt.on( "input", function(event) {
            var unos = $(this).val();
            $( "#datalist_movies" ).empty();
            $( "#datalist_movies_2" ).empty();

            //napravi Ajax poziv sa GET i dobij sve filmove (movie title) koja sadrže unos kao podstring
            $.ajax(
                {
                    type: "GET",
                    url: "<?php echo __SITE_URL; ?>/index.php?rt=index/autocomplite",
                    data:
                        {
                            q: unos
                        },
                    success: function( data )
                    {
                        //jednostavno sve što dobiješ od servera stavi u dataset.
                        console.log(data);
                        //var keywords = unos.split(' ').join('|');
                        //data2 = data.replace(new RegExp("(" + keywords + ")", "gi"), '<b>$1</b>');
                        //console.log(data2);
                        $( "#datalist_movies" ).html( data );
                        $( "#datalist_movies_2" ).html( data );
                    },
                    error: function( xhr, status )
                    {
                        if( status !== null )
                            console.log( "Error while AJAX call: " + status );
                    }
                } );
        } );
    } );
</script>
