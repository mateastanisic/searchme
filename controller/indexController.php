<?php 


class IndexController extends BaseController
{
	//samo preusmjeri na dashboard
	public function index() {
        $this->registry->template->title = 'Dashboard!';
        $this->registry->template->show( 'dashboard' );
		exit();
	}

	public function add_new_movie(){
	    //jesu li vrijednosti postavljene
        //jesu li vrijednosti neprazne -- nemoj dozvoliti neprazne unose imena filma
        if( isset($_POST['new_title']) && isset($_POST['new_category']) && isset($_POST['new_summary']) && isset($_POST['new_description']) && $_POST['new_title'] !== '' ){
            $sm = new searchme_service();
            $title = filter_var($_POST['new_title'], FILTER_SANITIZE_STRING);
            $categories = filter_var($_POST['new_category'], FILTER_SANITIZE_STRING);
            $summary = filter_var($_POST['new_summary'], FILTER_SANITIZE_STRING);
            $description = filter_var($_POST['new_description'], FILTER_SANITIZE_EMAIL);
            $sm->add_movie( $title, $categories, $summary, $description);

            $this->registry->template->message = 'You have successfully added new movie to the database.';
            $this->registry->template->option = '1';
            $this->registry->template->show( 'dashboard' );
            exit();
        }
        else{
            $this->registry->template->message = "Please fill out all values.";
            $this->registry->template->option = '1';
            $this->registry->template->show( 'dashboard' );
            exit();
        }
    }

    public function search(){
        if( isset($_POST['new_search']) && isset($_POST['and_or']) && $_POST['new_search'] !== '' ){
            $and_or = $_POST['and_or'];
            $search_input = $_POST['new_search'];

            $this->registry->template->option = '2';
            $this->registry->template->search_input = $search_input;
            $this->registry->template->and_or = $and_or;

            $sm = new searchme_service();
            $result = $sm->do_magic($and_or, $search_input);

            if( $result === false ){
                $this->registry->template->message = "Search failed!";
                $this->registry->template->show( 'dashboard' );
            }
            else{
                $this->registry->template->query = $result[0];
                $this->registry->template->movies = $result[1];
                $this->registry->template->show( 'dashboard' );
            }
        }
        else{
            $this->registry->template->message = "Please fill out search box.";
            $this->registry->template->option = '2';
            $this->registry->template->show( 'dashboard' );
        }
    }

    public function autocomplite(){
        if( !isset( $_GET['q'] ) ) echo "Input was not set.";
        else {
            $word = $_GET['q'];
            // Pronađi sve tražene filmove
            $sm = new searchme_service();
            $autocomplite = $sm->best_five($word);

            if( $autocomplite === false )  echo "";
            else echo $autocomplite;
        }
    }

    public function statistics(){
	    if( isset( $_POST['start'] ) && isset($_POST['end']) && $_POST['start'] !== '' && $_POST['end'] !== '' ){
            $hour_or_date = $_POST['date_hour'];
            $start_date = $_POST['start'];
            $end_date = $_POST['end'];

            $this->registry->template->option = '3';
            $this->registry->template->start = $start_date;
            $this->registry->template->end = $end_date;
            $this->registry->template->hour_or_date = $hour_or_date;

            $sm = new searchme_service();
            $table = $sm->granulate($hour_or_date, $start_date, $end_date);
            if( $table === false ){
                $this->registry->template->message = "Analysis gone wrong?!!";
                $this->registry->template->show( 'dashboard' );
            }
            else if( $table === "Please choose dates correctly!" ){
                $this->registry->template->message = $table;
                $this->registry->template->show( 'dashboard' );
            }
            $this->registry->template->header = $table[0];
            $this->registry->template->rows = $table[1];
            $this->registry->template->show( 'dashboard' );
        }
	    else{
            $this->registry->template->option = '3';
            $this->registry->template->message = "Please choose dates!";
            $this->registry->template->show( 'dashboard' );
        }


    }

}; 

?>