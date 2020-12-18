<?php
/*
 * Template Name: Lesson Template
 */
$user = wp_get_current_user();
if (!is_user_logged_in() || !in_array('student', (array) $user->roles)) {
    die('Only students can view material <a href="' . site_url() . '/wp-login.php">Return to home</a>');
}
?>
<style>
body {
    background-color: LightCyan;
    font-family: Arial, Helvetica, sans-serif;
}
.question_button {
    background-color: MintCream;
    padding: 1rem;
    border-style: solid;
    border-width: thin;
    border-radius: 1rem;
    text-align: center;
}
.dull_link {
    text-decoration: none;
    color: black;
}
</style>
<h2>Lesson: <?php echo ucfirst(get_query_var('lesson')); ?></h2>
<?php
$displayCounter = 1;
while (have_posts()) {
    the_post();
    if (alreadyAnswered(get_the_ID())) {
?>
    <div class="question_button">
        <?php echo $displayCounter; ?>. <a href="<?php the_permalink(); ?>"
        class="dull_link">Complete ☑</a><br/>
    </div>
<?php   } else {  /* if (alreadyAnswered(get_the_ID())) */ ?>
    <div class="question_button">
        <?php echo $displayCounter; ?>. <a href="<?php the_permalink(); ?>"
        class="dull_link">Unanswered □</a><br/>
    </div>
<?php   } /* end else (alreadyAnswered(get_the_ID())) */
    $displayCounter += 1;
}
?>
