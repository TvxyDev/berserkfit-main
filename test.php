<?php
require 'ligacao.php';
$res = $conn->query("SELECT * FROM peso WHERE id_user = 3 LIMIT 5");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo $conn->error;
}
?>
