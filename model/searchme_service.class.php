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
        if( !($this->check_if_there_is_allmovietsv_attribute()) ){
            //treba dodati taj atribut u bazu
            $this->add_allmovietsv_attribute();
        }

        //sad smo sigurni da imamo taj atribut, slijedi stvaranje gist atributa
        if( !($this->check_if_there_is_gist_index()) ){
            //dodajemo index u bazu
            $this->add_gist_index();
        }

        //sada treba napraviti tsquery od upita


        //i napokon, treba napraviti upit za search i rank

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
                      ORDER BY i.relname ";
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
    /*
    --------------------- ANALIZA  ------------------------
    */
};

?>