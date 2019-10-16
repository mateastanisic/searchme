<?php require_once __SITE_PATH . '/view/_header.php'; ?>
<?php require_once __SITE_PATH . '/view/addnewmovie.php'; ?>
<?php require_once __SITE_PATH . '/view/searchmovie.php'; ?>
<?php require_once __SITE_PATH . '/view/analytics.php'; ?>

    <!--makni sve s desne strane-->
    <script type="text/javascript">
        $(".right").hide();
    </script>

    <!-- i dodaj samo ono što treba, ako treba -->
    <?php
        //nismo prvi puta na ovoj stranici i nešto nam se mora pokazati
        if( isset($option) && ( $option === '1' || $option === '2' || $option === '3') ){
            switch ($option) {
                case '1':
                    echo '<script type="text/javascript">$("#add_new").show();</script>';
                    break;
                case '2':
                    echo '<script type="text/javascript">$("#search").show();</script>';
                    break;
                case '3':
                    echo '<script type="text/javascript">$("#analytics").show();</script>';
                    break;
            }
            unset($option);
        }

    ?>

<?php require_once __SITE_PATH . '/view/_footer.php'; ?>
