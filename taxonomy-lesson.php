<?php
$term = get_term_by('slug', get_query_var('lesson'), get_query_var('taxonomy'));
?>
<h2>Lesson: <?php echo $term->name; ?></h2>
<?php while (have_posts()) : the_post();
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
        <a href="<?php the_permalink(); ?>">Completed</a><br/>
    <?php } else { ?>
        <a href="<?php the_permalink(); ?>">Unanswered</a><br/>
    <?php }
    endwhile; ?>
