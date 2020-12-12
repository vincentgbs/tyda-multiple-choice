<?php
$term = get_term_by('slug', get_query_var('lesson'), get_query_var('taxonomy'));
?>
<h2>Lesson: <?php echo $term->name; ?></h2>
<?php while (have_posts()) : the_post(); ?>
    <a href="<?php the_permalink(); ?>">Questions</a>
<?php endwhile; ?>
