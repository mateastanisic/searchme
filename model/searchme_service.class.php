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
        return  $this->found_movies_with_rank_and_update_search_history($tsquery);
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

    function found_movies_with_rank_and_update_search_history($tsquery){
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

        /* ZA ANALIZU */
        //za potrebe analize upita kasnije ubacujemo pretraženi upit u prethodno stvorenu tablicu search_history
        /*
            CREATE TABLE search_history (
                search_input text,
                search_date date,
                search_time time without time zone
            );
         */
        $this->update_search_history($whole_tsquery);


        try{
            $db = DB::getConnection();
            //ts_headline() -> To present search results it is ideal to show a part of each document and how it is related to the query.
            $final_query = " SELECT movieid, ts_headline( title, to_tsquery('english', '" . $whole_tsquery . "')) AS title, 
                        ts_headline( description, to_tsquery('english', '" . $whole_tsquery . "')) AS description, 
                        ts_rank(allmovietsv, to_tsquery('english', '" . $whole_tsquery . "'), 2) AS rank
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
            $query = "SELECT title, ts_headline( summary, plainto_tsquery('english', '". $word ."') ) AS th, 
                      similarity(summary, '". $word ."') AS sml FROM movie WHERE summary % '". $word ."' ORDER BY sml DESC, title";
            $sm = $db->prepare( $query );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

        $row = $sm->fetch();
        if( $row === false ) return false;
        else {
            $movies = "<option value='". $row['title'] ."'>" . $row['th'] . "</option>" ;
            $i = 1;
            while( $row = $sm->fetch() ){
                if( $i > 5 ) return $movies;
                $movies = $movies ."<option value='". $row['title'] ."'>" . $row['th'] . "</option>";
                $i++;
            }
            return $movies;
        }
    }


    /*
    --------------------- ANALIZA  -------------------- ----
    */
    /* pretpostavka o postojećoj tablici
        CREATE TABLE search_history (
            search_input text,
            search_date date,
            search_time time without time zone
        );
    */
    function update_search_history($search_input){
        try{
            $db = DB::getConnection();
            $search_date = date("Y/m/d");
            $search_time = date("h:i:s");
            $query = 'INSERT INTO search_history(search_input, search_date, search_time) VALUES ( :search_input, :search_date, :search_time)';
            $sm = $db->prepare( $query );
            $sm->execute( array( 'search_input' => $search_input, 'search_date' => $search_date,'search_time' => $search_time ) );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

    }

    /* PIVOTIRANJE po satima/po danima */
    /* pretpostavka.1. izvrtili smo CREATE EXTENSION tablefunc; */
    /* pretpostavka.2. postoji tablica hours u bazi sa vrijednostima 0-23 (sati)*/
    /*
        CREATE TABLE hours( hour int);
        INSERT INTO hours VALUES(0);
	    INSERT INTO hours VALUES(1);
        INSERT INTO hours VALUES(2);
        INSERT INTO hours VALUES(3);
        INSERT INTO hours VALUES(4);
        INSERT INTO hours VALUES(5);
        INSERT INTO hours VALUES(6);
        INSERT INTO hours VALUES(7);
        INSERT INTO hours VALUES(8);
        INSERT INTO hours VALUES(9);
        INSERT INTO hours VALUES(10);
        INSERT INTO hours VALUES(11);
        INSERT INTO hours VALUES(12);
        INSERT INTO hours VALUES(13);
        INSERT INTO hours VALUES(14);
        INSERT INTO hours VALUES(15);
        INSERT INTO hours VALUES(16);
        INSERT INTO hours VALUES(17);
        INSERT INTO hours VALUES(18);
        INSERT INTO hours VALUES(19);
        INSERT INTO hours VALUES(20);
        INSERT INTO hours VALUES(21);
        INSERT INTO hours VALUES(22);
	    INSERT INTO hours VALUES(23);
     */
    function granulate($hour_or_date, $start_date, $end_date){
        if( $end_date < $start_date ) return "Please choose dates correctly!";
        else if( $hour_or_date === 'date' ){
            /* stvori temp table dates od početnog odabranog datuma do završnog odabranog datuma */
            $this->create_temp_table_days();
            $header = [];

            $begin = new DateTime($start_date);
            $end = new DateTime($end_date);

            for($i = $begin; $i <= $end; $i->modify('+1 day')){
                $date = $i->format("Y-m-d");
                $this->insert_day_in_temp_table_days($date);
                $y = substr($date,0,4);
                $m = substr($date,5,2);
                $d = substr($date,8,2);
                $string = 'd' . $y .'_' . $m . '_' . $d;
                array_push($header, $string);
            }

            $y_s = substr($start_date,0,4);
            $m_s = substr($start_date,5,2);
            $d_s = substr($start_date,8,2);
            $y_e = substr($end_date,0,4);
            $m_e = substr($end_date,5,2);
            $d_e = substr($end_date,8,2);
            $sqlquery1 = "SELECT search_input, search_date, COUNT(*) FROM search_history WHERE EXTRACT( YEAR FROM search_date ) BETWEEN ". $y_s . " AND " . $y_e ." 
                          AND EXTRACT( MONTH FROM search_date ) BETWEEN ". $m_s . " AND " . $m_e ." AND EXTRACT( DAY FROM search_date ) BETWEEN ". $d_s . " AND " . $d_e ."  
                          GROUP BY search_input,search_date ORDER BY search_input, search_date";
            $sqlquery2 = 'SELECT day FROM days ORDER BY day';
            $sqlquery3 = '';
            for ( $i = 0; $i < count($header); $i++ ){
                $sqlquery3 .= $header[$i] . ' bigint';
                if( $i !== count($header)-1 ) $sqlquery3 .= ',';
            }

            //pristupamo bazi za actual pivotiranje
            try{
                $db = DB::getConnection();
                $query = "SELECT * FROM crosstab('". $sqlquery1 . "','". $sqlquery2 ."') AS pivot_table( tsquery TEXT,". $sqlquery3.") ORDER BY tsquery";
                $sm = $db->prepare( $query );
                $sm->execute( );
            }
            catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

            $rows = [];
            while( $row = $sm->fetch() ) array_push($rows, $row );

            //obriši privremenu tablicu
            $this->drop_temp_table_days();

            //vrati
            if( count($rows) === 0 ) return false;
            else return [$header, $rows];
        }
        else if( $hour_or_date === 'hour' ){
            $header = ['s0001', 's0102', 's0203', 's0304', 's0405', 's0506',  's0607', 's0708', 's0809', 's0910', 's1011',  's1112',
                        's1213', 's1314', 's1415', 's1516',  's1617', 's1718', 's1819', 's1920', 's2021', 's2122', 's2223', 's2300'];

            $y_s = substr($start_date,0,4);
            $m_s = substr($start_date,5,2);
            $d_s = substr($start_date,8,2);
            $y_e = substr($end_date,0,4);
            $m_e = substr($end_date,5,2);
            $d_e = substr($end_date,8,2);
            $sqlquery1 = "SELECT search_input, EXTRACT( HOUR FROM search_time) AS hours, COUNT(*) FROM search_history WHERE EXTRACT( YEAR FROM search_date ) BETWEEN ". $y_s . " AND " . $y_e ." 
                          AND EXTRACT( MONTH FROM search_date ) BETWEEN ". $m_s . " AND " . $m_e ." AND EXTRACT( DAY FROM search_date ) BETWEEN ". $d_s . " AND " . $d_e ."  
                          GROUP BY search_input,hours ORDER BY search_input, hours";
            $sqlquery2 = 'SELECT hour FROM hours ORDER BY hour';
            $sqlquery3 = 's0001 bigint, s0102 bigint, s0203 bigint, s0304 bigint, s0405 bigint, s0506 bigint, s0607 bigint, 
	                      s0708 bigint, s0809 bigint, s0910 bigint, s1011 bigint, s1112 bigint, s1213 bigint, s1314 bigint, s1415 bigint, 
	                      s1516 bigint, s1617 bigint, s1718 bigint, s1819 bigint, s1920 bigint, s2021 bigint, s2122 bigint, s2223 bigint, s2300 bigint';

            //pristupamo bazi za actual pivotiranje
            try{
                $db = DB::getConnection();
                $query = "SELECT * FROM crosstab('". $sqlquery1 . "','". $sqlquery2 ."') AS pivot_table( tsquery TEXT,". $sqlquery3.") ORDER BY tsquery";
                $sm = $db->prepare( $query );
                $sm->execute( );
            }
            catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }

            $rows = [];
            while( $row = $sm->fetch() ) array_push($rows, $row );

            //vrati
            if( count($rows) === 0 ) return false;
            else return [$header, $rows];
        }
        else return false;
    }

    function create_temp_table_days(){
        try{
            $db = DB::getConnection();
            $query = 'CREATE TEMP TABLE days( day date )';
            $sm = $db->prepare( $query );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

    function drop_temp_table_days(){
        try{
            $db = DB::getConnection();
            $query = 'DROP TABLE days';
            $sm = $db->prepare( $query );
            $sm->execute( );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

    function insert_day_in_temp_table_days($day){
        try{
            $db = DB::getConnection();
            $query = 'INSERT INTO days(day) VALUES ( :day)';
            $sm = $db->prepare( $query );
            $sm->execute( array( 'day' => $day ) );
        }
        catch( PDOException $e ) { exit( 'PDO error ' . $e->getMessage() ); }
    }

};

?>