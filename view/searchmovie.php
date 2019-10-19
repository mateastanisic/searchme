
<div class="right" id="search">

    <!-- search box -->
    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/search" >
        <div id="search_box" >
            <?php
            //u message se nalazi tekst po kojemu smo pretraživali filmove
            //u var option se nalazi vrijednost jesmo li pretraživali filmove s opcijom OR ili AND
            if( isset($search_input) && strlen($search_input)  && isset($and_or) && strlen($and_or) ){
                echo '<input type="text" name="new_search" class="nice_input" placeholder="', $search_input ,'" style="font-style:italic" "/>';
                ?><button type="submit" class="search_button"> &#187; </button> <br /><?php
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
                echo '<input type="text" name="new_search" class="nice_input" placeholder="search" style="font-style:italic"/>';?>
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
            echo '<h2> Search result:</h2>';


            echo 'Query string: ';?>
            <pre lang="SQL"><code>
                <?php echo $query; ?>
            </code></pre> <br /><?php

            if( is_array($movies) ){
                echo '<p> Number of movies found: <b>', count($movies),'</b></p>';
                echo '<table>';
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
        else if( isset($message) && strlen($message) ) {
            //nismo ništa upisali u search box
            echo '<p>', $message, '</p>';
            unset($message);
        }

    ?>

</div>
