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
    .disabled_link {
        pointer-events: none;
        color: grey;
    }
    </style>
</head>
<?php
    $start = "2020-12-01"; /* need to pull from database */
    $lessonObject = get_queried_object();
    $dates = calculateLessonDates([
        'lesson'=>$lessonObject,
        'start'=>$start,
        'open'=>get_field('week_open', $lessonObject),
        'due'=>get_field('week_due', $lessonObject),
    ]);
?>
<body>
    <div id="lesson_body">
        <h2>Lesson: <?php echo ucfirst(get_query_var('lesson')); ?></h2>
        <input type="hidden" id="lesson_status" value="<?php echo $dates['status']; ?>" />
        <p>Open: <?php echo date_format($dates['open'], "Y-m-d"); ?></p>
        <p>Due: <?php echo date_format($dates['due'], "Y-m-d"); ?></p>
        <?php
        $displayCounter = 0;
        while (have_posts()) {
            the_post();
            $displayCounter++;
            if (getAnsweredStatus(get_the_ID())) {
                $displayText = 'Completed ☑';
            } else {
                $displayText = 'Unanswered □';
            }
        ?>
            <div class="question_option">
                <a href="<?php the_permalink(); ?>" class="dull_link disabled_link">
                <?php echo $displayCounter . '. ' . $displayText; ?></a><br/>
            </div>
        <?php } /* end while (have_posts()) */ ?>
    </div>
</body>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (document.querySelector("#lesson_status").value == 'open') {
        let options = document.querySelectorAll('.disabled_link');
        Array.from(options).forEach(function(option) {
            option.classList.remove('disabled_link');
        });
    }
});
</script>
</html>
