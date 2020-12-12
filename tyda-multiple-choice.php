<?php
/*
  Plugin Name: Tyda Multiple Choice Plugin
  Plugin URI: https://github.com/vincentgbs/tyda-multiple-choice
  Description: This plugin requires the advanced-custom-fields plugin. The 'question' post needs subfields: 'option1', 'option2', 'option3', 'answer', 'next_question'. You can optionally add the 'question_id' to the 'correct' and 'wrong' post types to track them in the backend. The members plugin is useful for adding the 'student' role.
  Version: 0.1
  Author: Vincent Hu
  Author URI: https://www.vincenthu.dev
*/
define('QUESTIONS_MAX_WRONG', 5);
define('QUESTIONS_WRONG_TIMER', '1 hour ago');

register_activation_hook(__FILE__, 'require_parent_plugin');
function require_parent_plugin(){
    if (!is_plugin_active( 'advanced-custom-fields/acf.php')) {
        wp_die('Sorry, but this plugin requires the parent plugin (ACF) to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}

add_action('init', 'add_questions_post_types');
function add_questions_post_types() {
    register_post_type('question', [
        'supports' => ['title', 'editor'],
        'public' => true,
        'labels' => [
            'name' => 'Question',
            'add_new_item' => 'Add New Question',
            'edit_item' => 'Edit Question',
            'all_items' => 'All Questions',
            'singular_name' => 'Question'
        ],
        'menu_icon' => 'dashicons-edit'
    ]); /* end register_post_type(question) */

    register_post_type('correct', [
        'supports' => ['editor'],
        'public' => true,
        'labels' => [
            'name' => 'Correct',
            'add_new_item' => 'Add New Correct Answer',
            'edit_item' => 'Edit Correct Answer',
            'all_items' => 'All Correct Answers',
            'singular_name' => 'Correct Answer'
        ],
        'menu_icon' => 'dashicons-saved'
    ]); /* end register_post_type(correct) */

    register_post_type('wrong', [
        'supports' => ['editor'],
        'public' => true,
        'labels' => [
            'name' => 'Wrong',
            'add_new_item' => 'Add New Wrong Answer',
            'edit_item' => 'Edit Wrong Answer',
            'all_items' => 'All Wrong Answers',
            'singular_name' => 'Wrong Answer'
        ],
        'menu_icon' => 'dashicons-lock'
    ]); /* end register_post_type(wrong) */

} /* end add_questions_post_types() */

add_action('rest_api_init', 'question_route');
function question_route() {
    register_rest_route('university/v1', 'answer', [
        'methods' => 'POST',
        'callback' => 'questionsGetAnswer',
        'permission_callback' => '__return_true'
    ]);
}
function questionsGetAnswer($post) {
    $user = wp_get_current_user();
    if (!is_user_logged_in() || !in_array('student', (array) $user->roles)) {
        die('Only students can answer a question');
    }
    $questionId = preg_replace('/\D/', '', sanitize_text_field($post['questionId']));
    $answer = sanitize_text_field($post['answer']);
    if (get_post_type($questionId) != 'question') {
        die('Invalid questionId: ' . $questionId);
    }
    $alreadyAnswered = new WP_Query([
        'author' => get_current_user_id(),
        'post_type' => 'correct',
        'meta_query' => [
            ['key'      => 'question_id',
            'compare'   => '=',
            'value'     => $questionId]
        ]
    ]);
    if ($alreadyAnswered->found_posts > 0) {
        die('Done'); /* You already answered this question correctly */
    }
    $cramGuard = new WP_Query([
        'author' => get_current_user_id(),
        'post_type' => 'wrong',
        'date_query' => [
            'after' => QUESTIONS_WRONG_TIMER
        ]
    ]);
    if ($cramGuard->found_posts >= QUESTIONS_MAX_WRONG) {
        die('Take a break, it is better to learn the material slowly');
    }
    $getAnswer = new WP_Query([
        'post_type' => 'question',
        'p' => $questionId
    ]);
    while($getAnswer->have_posts()) {
        $getAnswer->the_post();
        $correctAnswer = get_field('answer');
    }
    if (isset($correctAnswer) && $correctAnswer == $answer) {
        wp_insert_post([
            'post_type' => 'correct',
            'post_status' => 'publish',
            'meta_input' => [
                'question_id' => $questionId,
            ]
        ]);
        return 'Correct';
    } else {
        wp_insert_post([
            'post_type' => 'wrong',
            'post_status' => 'publish',
            'meta_input' => [
                'question_id' => $questionId,
            ]
        ]);
        return 'Wrong';
    }
}

add_filter('template_include', 'question_page_template');
function question_page_template($template) {
    $file_name = 'single-question.php';
    if (is_singular('question')) {
        if (locate_template($file_name)) {
            $template = locate_template($file_name);
        } else {
            $template = dirname(__FILE__) . '/' . $file_name;
        }
    }
    return $template;
}
