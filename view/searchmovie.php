
<div class="right" id="search">

    <!-- search box -->
    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/search" >
        <div id="search_box" class="transparent">
            <?php
            //u message se nalazi tekst po kojemu smo pretraživali filmove
            //u var option se nalazi vrijednost jesmo li pretraživali filmove s opcijom OR ili AND
            if( isset($search_input) && strlen($search_input)  && isset($and_or) && strlen($and_or) ){
                echo '<input type="text" name="search" class="nice_input" id="new_search" placeholder=', $search_input ,'/>';
                if( $and_or === 'OR'){
                    echo '<input type="radio" name="and_or" value="and" > AND <br />';
                    echo '<input type="radio" name="and_or" value="or" checked> OR <br />';
                }
                else if( $and_or === 'AND' ){
                    echo '<input type="radio" name="and_or" value="and" checked> AND <br />';
                    echo '<input type="radio" name="and_or" value="or" > OR <br />';
                }
                unset($search_input);
                unset($and_or);
            }
            else{
                //tek smo otvorili ovu opciju
                echo '<input type="text" name="search" class="nice_input" placeholder="search"/>';
                echo '<input type="radio" name="and_or" value="and" > AND <br />';
                echo '<input type="radio" name="and_or" value="or" checked> OR <br />';
            }
            ?>
            <button type="submit"> &#187; </button> <br /><br />
        </div>
    </form>


    <!-- ako smo u prethodnom slučaju pretraživali po nečemu ispisuje se rezultat pretraživanja -->
    <?php
        if( isset($query)  && isset($documents_name) && isset($documents_rank) && strlen($query) ){
            //dobili smo neki ispis
            //traba ispisati rezultat pretraživanja
            echo '<h2> Search result:</h2>';


            echo 'Query string: <br />';
            echo '<div>', $query, '</div> <br />';

            if( count($documents_name)>0 ){
                echo '<p> Number of movies found:', count($documents_name),'</p>';
                echo '<table>';
                echo '<tr><th> MOVIE NAME </th> <th> RANK </th></tr>';
                for($i = 0; $i<count($documents_name); $i++ ){
                    echo '<tr>';
                    echo '<td>', $documents_name[$i], '</td>';
                    echo '<td>', $documents_rank[$i], '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            else{
                echo '<p> No movies were found. </p>';
            }
            unset($query);
            unset($documents_name);
            unset($documents_rank);
        }
        else if( isset($message) && strlen($message) ) {
            //nismo ništa upisali u search box
            echo '<p>', $message, '</p>';
            unset($message);
        }
        else{
            //nepoznata situacija?!
            echo '<p> No search result?!?! </p><br />';
        }

    ?>

</div>
