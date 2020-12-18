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
.dull_link {
    text-decoration: none;
    color: black;
}
.question_option {
    background-color: MintCream;
    margin: .2rem 1rem;
    padding: 1rem;
    border-style: solid;
    border-width: thin;
    border-radius: 1rem;
    text-align: center;
}
</style>
<h2>Lesson: <?php echo ucfirst(get_query_var('lesson')); ?></h2>
<?php
$displayCounter = 0;
while (have_posts()) {
    the_post();
    $displayCounter++;
    if (alreadyAnswered(get_the_ID())) {
        $displayText = 'Completed ☑';
    } else {
        $displayText = 'Unanswered □';
    }
?>
    <div class="question_option">
        <?php echo $displayCounter; ?>. <a href="<?php the_permalink(); ?>"
        class="dull_link"><?php echo $displayText; ?></a><br/>
    </div>
<?php } /* end while loop */ ?>
