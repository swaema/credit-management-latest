<?php
include_once('../Classes/UserAuth.php');
UserAuth::logout();
header('Location: ../index.php')
?>