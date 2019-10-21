
<div class="right" id="statistics">

    <h2> time analysis  </h2> <br>
    <form method="post" action="<?php echo __SITE_URL; ?>/index.php?rt=index/statistics" >
        <div class="tab">
            <h3> choose date period for time analysis</h3>
            start date: <input type="date" class="datepicker"  name="start"  min="2019-01-01" max="2019-10-30"><br><br>
            end date:<input type="date" class="datepicker" name="end"  min="2019-01-01" max="2019-10-30"><br><br>

            analyse data <input type="radio" name="date_hour" value="date" checked> per date <input type="radio" name="date_hour" value="hour" > per hour <br> <br>
            <button type="submit" class="analyse_button" > analyse </button> <br />
        </div>
    </form>
    <br><br>
    <div id="ovdje"></div>
    <br><br>
    <div class="table">
    <?php
        if( isset($header) && count($header) && isset($rows) && count($rows) ){
            ?>
                <table class="crtaj" id="unique">
                    <tr class="crtaj">
                        <th class="crtaj"> search input </th>
                        <?php for( $i = 0; $i<count($header); $i++) echo '<th class="crtaj">' . $header[$i] . '</th>'; ?>
                    </tr>
                    <?php
                        foreach( $rows as $row ){
                            echo '<tr class="crtaj">';
                            echo '<td class="crtaj">'. $row['tsquery'] . '</td>';
                            for( $i = 0; $i<count($header); $i++)  echo '<td class="crtaj">'. $row[$header[$i]] . '</td>';
                            echo '</tr>';
                        }
                    ?>
                </table>
            <?php
        }
        else if( isset($message) ){
            echo '<p>', $message, '</p>';
            unset($message);
        }
    ?>
    </div>
    <br><br><br><br>
</div>

<script>
    $("document").ready(function() {
        function split($table, chunkSize) {
            var cols = $("th", $table).length - 1;
            var n = cols / chunkSize;
            if( n <= 0 ) break;

            for (var i = 1; i <= n; i++) {
                $("<br/>").appendTo("#ovdje");
                var $newTable = $table.clone().appendTo("#ovdje");
                for (var j = cols + 1; j > 1; j--) {
                    if (j + chunkSize - 1 <= chunkSize * i || j > chunkSize * i + 1) {
                        $('td:nth-child(' + j + '),th:nth-child(' + j + ')', $newTable).remove();
                    }
                }
            }
            $('.table').hide();
        }

        split($("#unique"), 10);

    });
</script>
