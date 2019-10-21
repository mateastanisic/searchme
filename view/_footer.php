
<div class="footer">
    <small>
        &copy; <a href="mailto:stmatea@student.math.hr">Matea Stanišić</a><br />
    </small>
</div>

<script type="text/javascript">
    $("document").ready(function() {
        //pritiskom na naslov "vraćamo se na početnu stranicu" ~ hidamo sve s desne strane
        $('#page_name').on( "click", function() {
            var loc1 = window.location.pathname;
            var loc2 = {
                url : '?rt=index/index'
            };
            console.log(loc1);
            window.location.assign(loc1+loc2.url);
        });
        //pritiskom na neku od opcija otvara nam se ili forma za unos novog filma u bazu
        //ili forma za pretraživanje po bazi
        //ili radimo analizu pretraživanja filmova
        $(".options").on("click", function(){
            //izvuci indeks ( ili je op1 ili op2 ili op3 )
            var id = $(this).attr("id");
            switch( id.substr(2) ) {
                case '1':
                    $("#search").hide();
                    $("#statistics").hide();
                    $("#add_new").show();
                    console.log("here1");
                    break;
                case '2':
                    $("#add_new").hide();
                    $("#statistics").hide();
                    $("#search").show();
                    console.log("here2");
                    break;
                case '3':
                    $("#search").hide();
                    $("#add_new").hide();
                    $("#statistics").show();
                    console.log("here3");
                    break;
                default:
                    $(".right").hide();
            }

        });
    } )
</script>

</body>
</html>