<?php

class searchme_service {
    /*
    --------------------- DODAJ NOVI FILM U BAZU  ------------------------
    */
    function add_movie( $title, $categories, $summary, $description){
        try{
            $db = DB::getConnection();
            $movieid = $this->new_movie_id();
            $sm = $db->prepare( 'INSERT INTO movie(movieid, title, categories, summary, description ) VALUES (:movieid, :title, :categories, :summary, :description)' );
            $sm->execute( array( 'movieid' => $movieid, 'title' => $title, 'categories' => $categories, 'summary' => $summary, 'description' => $description) );

            $query = "UPDATE movie SET allmovietsv =  
                      setweight (to_tsvector( 'english' , title ), 'A') || setweight( to_tsvector('english',description), 'B') || setweight(to_tsvector('english', summary), 'C') 
                          || setweight( to_tsvector('english', categories), 'D') WHERE movieid=:movieid ";
            $sm2 = $db->prepare(  $query );
            $sm2->execute( array( 'movieid' => $movieid) );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

    function new_movie_id(){
        try{
            $db = DB::getConnection();
            $sm = $db->prepare( 'SELECT movieid FROM movie ORDER BY movieid DESC LIMIT 1' );
            $sm->execute();
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        $row = $sm->fetch();
        if( $row === false ) return null;
        else return  1 + $row['movieid'];
    }

    /*
    --------------------- PRETRAŽI BAZU  ------------------------
    */
    function do_magic($search_type, $search_input){
        //za ubrzanje pretrage koristim postgresql operator @@
        //za korištenje tog operatora potreban nam je tekst u formatu tsvektora i upit u formatu tsquery-a

        /* TSVECTOR */
        //dodavanje novog atributa (ako ga već nema) tablice movie koji sadrži tsvektor (svi atributa ali s različitim "težinama" )
        // A -> title
        // B -> description
        // C -> summary
        // D -> categories
        if( !($this->check_if_there_is_allmovietsv_attribute()) ){
            //treba dodati taj atribut u bazu
            $this->add_allmovietsv_attribute();
        }

        /* TSQUERY */
        //sada treba napraviti tsquery format upita
        $tsquery = $this->make_tsquery($search_type, $search_input);
        if( $tsquery === false || count($tsquery) === 0 ){
            //precaution
            return false;
        }

        /* GIST INDEKS */
        //za dodatno ubrzanje koristimo invertirani indeks (gist je 3x brži od gina)
        //sad smo sigurni da imamo taj atribut, slijedi stvaranje gist atributa
        if( !($this->check_if_there_is_gist_index()) ){
            //dodajemo index u bazu
            $this->add_gist_index();
        }

        //i napokon, treba napraviti upit za search i rank
        //funkcija vraća query s kojim smo doznali "rezultat" i rezultat pretraživanja baze
        return  $this->found_movies_with_rank($tsquery);
    }

    function check_if_there_is_allmovietsv_attribute(){
        try{
            $db = DB::getConnection();
            $query = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME LIKE 'movie' ";
            $sm = $db->prepare( $query );
            $sm->execute(  );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        while( $row = $sm->fetch() ){
            if( $row['column_name'] === 'allmovietsv' ) return true;
        }
        return false;
    }

    function add_allmovietsv_attribute(){
        try{
            //provjereno radi
            $db = DB::getConnection();
            $sm = $db->prepare( "ALTER TABLE movie ADD allmovietsv TSVector" );
            $sm->execute( );

            $query = "UPDATE movie SET allmovietsv =  setweight (to_tsvector( 'english' , title ), 'A') || setweight( to_tsvector('english',description), 'B') || setweight(to_tsvector('english', summary), 'C') || setweight( to_tsvector('english', categories), 'D')";
            $sm2 = $db->prepare(  $query );
            $sm2->execute();
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

    function check_if_there_is_gist_index(){
        try{
            $db = DB::getConnection();
            $query = "SELECT t.relname AS table_name, i.relname AS index_name, a.attname AS column_name  
                      FROM pg_class t, pg_class i, pg_index ix, pg_attribute a 
                      WHERE t.oid = ix.indrelid AND i.oid = ix.indexrelid AND a.attrelid = t.oid AND a.attnum = ANY(ix.indkey) AND t.relname LIKE 'movie' AND t.relkind = 'r'
                      ORDER BY i.relname";
            $sm = $db->prepare( $query );
            $sm->execute(  );

        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        while( $row = $sm->fetch() ){
            if( $row['index_name'] === 'gistindex' ) return true;
        }
        return false;

    }

    function add_gist_index(){
        try{
            $db = DB::getConnection();
            $sm = $db->prepare( "CREATE INDEX gistindex ON movie USING gist(allmovietsv)" );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

    function make_tsquery($search_type, $search_input){
        $tsquery = [];
        $tsquery_input = "";
        $is_phrase = false;
        $operator_added = false;

        if( !($search_type === 'AND' || $search_type === 'OR') ) return false; //precaution

        for( $i = 0; $i < strlen($search_input); $i++ ){
            if( $search_input[$i] === '"' || $search_input[$i] === "'" ){
                if( $is_phrase ) {
                    //jedna fraza završena
                    $is_phrase = false;
                    $tsquery_input = $tsquery_input . ')';

                    if( $search_type === "OR" ){
                        //ako nam je 0R operator pretraživanja
                        array_push($tsquery, $tsquery_input);
                        $tsquery_input = "";
                    }
                    else{
                        //AND
                        $tsquery_input = $tsquery_input . ' & ';
                    }
                    //dodali smo operator u oba slučaja
                    $operator_added = true;
                }
                else if( !$is_phrase ){
                    $is_phrase = true;
                    $tsquery_input = $tsquery_input . '(';
                    $operator_added = false;
                }
            }
            else{
                //nije početak/završetak fraze
                if( $search_input[$i] === ' ' ){
                    if( $is_phrase && !$operator_added ){
                        //u frazi smo i nije dodan operator
                        $tsquery_input = $tsquery_input . ' & ';
                        $operator_added = true;
                    }
                    else if( $is_phrase && $operator_added ) continue;
                    else if( !$is_phrase && !$operator_added ){
                        //nismo u frazi i nije dodan operator
                        if( $search_type === "OR" ){
                            //ako nam je 0R operator pretraživanja
                            array_push($tsquery, $tsquery_input);
                            $tsquery_input = "";
                        }
                        else{
                            //AND
                            $tsquery_input = $tsquery_input . ' & ';
                        }
                        //u oba slučaja smo dodali operator
                        $operator_added = true;
                    }
                    else if( !$is_phrase && $operator_added ) continue;
                }
                else {
                    //nije razmak, nisu navodnici -> unos koji pretražujemo
                    $tsquery_input = $tsquery_input . $search_input[$i];
                    $operator_added = false;
                }
            }
        }
        //ako je AND, onda je duljina vraćenog polja jednaka 1
        if( $search_type === "AND" ) array_push($tsquery, $tsquery_input);
        else if( $search_type === "OR" && ( count($tsquery) === 0 || $tsquery[count($tsquery)-1] !== $tsquery_input ) ) array_push($tsquery, $tsquery_input);
        return $tsquery;
    }

    function found_movies_with_rank($tsquery){
        //imamo varijablu $tsquery(polje), koje je duljine 1 ako je operator pretraživanja AND (ili smo pretraživali samo po jednoj frazi/riječi)
        //kako će upit s kojim pretražujemo bazu ovisiti o tome hoće li operator biti OR ili AND ( dio sa operatorom @@ )
        //zasebno ćemo napisati string ovisno o poslanom polju $tsquery

        //osigurali smo prije da ovo polje sigurno ima barem jedan element
        //pa je početak stringa ovakav:
        $string = "allmovietsv @@ to_tsquery('english', '" . $tsquery[0] . "' )";
        $whole_tsquery = $tsquery[0];
        //inače, ako je operator OR onda imamo
        if( count($tsquery) > 1 ) {
            for($i = 1; $i < count($tsquery); $i++ ){
                $string = $string . "  OR allmovietsv @@ to_tsquery('english', '" . $tsquery[$i] . "' )";
                $whole_tsquery = $whole_tsquery . " | " . $tsquery[$i];
            }
        }

        try{
            $db = DB::getConnection();
            //ts_headline() -> To present search results it is ideal to show a part of each document and how it is related to the query.
            $final_query = " SELECT movieid, ts_headline( title, to_tsquery('english', '" . $whole_tsquery . "')) AS title, 
                        ts_headline( description, to_tsquery('english', '" . $whole_tsquery . "')) AS description, 
                        ts_rank(allmovietsv, to_tsquery('english', '" . $whole_tsquery . "')) AS rank
                        FROM movie WHERE " . $string . " ORDER BY rank DESC";
            $sm = $db->prepare( $final_query );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        $movies = [];
        while( $row = $sm->fetch() ){
            array_push($movies, new Movie( $row['movieid'], $row['title'], $row['description'], $row['rank'] ) );
        }

        if( count($movies) === 0 ) return [$final_query, "There are no results with given input!" ];
        else return [$final_query, $movies];
    }

    /* AUTOCOMPLITION */
    /* this part had to be done before using functions like similarity, <->, % ...
        CREATE EXTENSION pg_trgm;
        CREATE INDEX title_index ON movie USING GIST(title gist_trgm_ops);
    */
    function best_five($word){
        try{
            $db = DB::getConnection();
            $query = 'SELECT ts_headline( title, to_tsquery("english", "'. $word .'") ) AS title, 
                      similarity(title, "'. $word .'"  ) AS sml FROM movie WHERE title % "'. $word .'" ORDER BY sml DESC, title';
            $sm = $db->prepare( $query );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        $row = $sm->fetch();
        if( $row === false ) return false;
        else {
            $movies = $row['title'];
            $i = 1;
            while( $row = $sm->fetch() && $i<5 ){
                array_push($movies, $row['title']);
                $i++;
            }
            return $movies;
        }
    }


    /*
    --------------------- ANALIZA  -------------------- ----
    */
};

?>