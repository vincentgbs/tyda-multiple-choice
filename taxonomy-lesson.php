<?php
/*
 * Template Name: Lesson Template
 */
$user = wp_get_current_user();
if (!is_user_logged_in() || !in_array('student', (array) $user->roles)) {
    die('Only students can view the material');
}
$term = get_term_by('slug', get_query_var('lesson'), get_query_var('taxonomy'));
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
<h2>Lesson: <?php echo $term->name; ?></h2>
<?php
    $displayCount = 1;
    while (have_posts()) : the_post();
    $alreadyAnswered = new WP_Query([
        'author' => get_current_user_id(),
        'post_type' => 'correct',
        'meta_query' => [
            ['key'      => 'question_id',
            'compare'   => '=',
            'value'     => get_the_ID()]
        ]
    ]);
    if ($alreadyAnswered->found_posts > 0) { ?>
        <div class="question_button">
        <?php echo $displayCount; ?>. <a href="<?php the_permalink(); ?>"
            class="dull_link">Answered ☑</a><br/>
        </div>
    <?php } else { ?>
        <div class="question_button">
        <?php echo $displayCount; ?>. <a href="<?php the_permalink(); ?>"
            class="dull_link">Question □</a><br/>
        </div>
    <?php }
    $displayCount += 1;
    endwhile; ?>
