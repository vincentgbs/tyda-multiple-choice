<?php
/*
 * Template Name: Lesson Template
 */
$user = wp_get_current_user();
if (!is_user_logged_in() || !in_array('student', (array)$user->roles)) {
    die('Only students can view the material <br />
        <a href="' . site_url() . '/wp-login.php">Login</a>');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo ucfirst(get_query_var('lesson')); ?></title>
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
        margin: .2rem 0;
        padding: 1rem;
        border-style: solid;
        border-width: thin;
        border-radius: 1rem;
        text-align: center;
    }
    </style>
</head>
<body>
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
    <?php } /* end while (have_posts()) */ ?>
</body>
</html>
