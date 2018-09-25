<?php $link = Uri::create("/User/ForgottenPassword?e=$email&ts=$timestamp&h=$hash&site=$site"); ?>
<p>A request was made to reset the password on cards.leinsterhockey.ie for
this account.</p>

<p>To reset your password please click on the following link:<br>
<a href='<?= $link ?>'><?= $link ?></a></p>

<p>If you did not make this request, please ignore this email.</p>
