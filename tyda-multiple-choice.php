<?php
/*
  Plugin Name: Tyda Multiple Choice Plugin
  Plugin URI: https://github.com/vincentgbs/tyda-multiple-choice
  Description: This plugin requires the advanced-custom-fields plugin and the members plugin. This is a simple lesson creator with multiple choice answers.
  Version: 0.1
  Author: Vincent Hu
  Author URI: https://www.vincenthu.dev
*/
define('QUESTIONS_MAX_WRONG', 5);
define('QUESTIONS_WRONG_TIMER', '1 hour ago');

register_activation_hook(__FILE__, 'require_parent_plugin');
function require_parent_plugin(){
    if (!is_plugin_active('advanced-custom-fields/acf.php')) {
        wp_die('Sorry, but this plugin requires the parent plugin (advanced custom fields) to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
    }
    if (!is_plugin_active('members/members.php')) {
        wp_die('Sorry, but this plugin requires the parent plugin (memberpress: members) to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
    }
}

add_action('init', 'add_question_taxonomy_types');
function add_question_taxonomy_types() {
    $labels = [
        'name' => 'Lessons',
        'singular_name' => 'Lesson',
        'search_items' => 'Search Lessons',
        'all_items' => 'All Lessons',
        'parent_item' => 'Parent Lesson',
        'parent_item_colon' => 'Parent Lesson:',
        'edit_item' => 'Edit Lesson',
        'update_item' => 'Update Lesson',
        'add_new_item' => 'Add New Lesson',
        'new_item_name' => 'New Lesson Name',
        'menu_name' => 'Lesson'
    ];
    $rewrite = ['slug' => 'lessons'];
    $args = [
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => $rewrite,
    ];
    register_taxonomy('lesson', ['question'], $args);
} /* end add_question_taxonomy_types() */

add_action('init', 'add_questions_post_types');
function add_questions_post_types() {
    $labels = [
        'name' => 'Question',
        'add_new_item' => 'Add New Question',
        'edit_item' => 'Edit Question',
        'all_items' => 'All Questions',
        'singular_name' => 'Question',
    ];
    register_post_type('question', [
        'supports' => ['title', 'editor'],
        'public' => true,
        'labels' => $labels,
        'menu_icon' => 'dashicons-edit',
    ]); /* end register_post_type(question) */

    $labels = [
        'name' => 'Correct',
        'add_new_item' => 'Add New Correct Answer',
        'edit_item' => 'Edit Correct Answer',
        'all_items' => 'All Correct Answers',
        'singular_name' => 'Correct Answer',
    ];
    register_post_type('correct', [
        'supports' => ['editor'],
        'public' => true,
        'labels' => $labels,
        'menu_icon' => 'dashicons-saved',
    ]); /* end register_post_type(correct) */

    $labels = [
        'name' => 'Wrong',
        'add_new_item' => 'Add New Wrong Answer',
        'edit_item' => 'Edit Wrong Answer',
        'all_items' => 'All Wrong Answers',
        'singular_name' => 'Wrong Answer',
    ];
    register_post_type('wrong', [
        'supports' => ['editor'],
        'public' => true,
        'labels' => $labels,
        'menu_icon' => 'dashicons-lock',
    ]); /* end register_post_type(wrong) */
} /* end add_questions_post_types() */

add_action('rest_api_init', 'question_route');
function question_route() {
    register_rest_route('university/v1', 'answer', [
        'methods' => 'POST',
        'callback' => 'questions_get_answer',
        'permission_callback' => '__return_true',
    ]);
}
function getAnsweredStatus($questionId, $userId=false) {
    if (!$userId) {
        $userId = get_current_user_id();
    }
    $alreadyAnswered = new WP_Query([
        'author' => $userId,
        'post_type' => 'correct',
        'meta_query' => [
            ['key'      => 'question_id',
            'compare'   => '=',
            'value'     => $questionId]
        ]
    ]);
    if ($alreadyAnswered->found_posts > 0) {
        return true;
    }
    return false;
}
function getAttempts($userId=false) {
    if (!$userId) {
        $userId = get_current_user_id();
    }
    $getAttempts = new WP_Query([
        'author' => $userId,
        'post_type' => 'wrong',
        'date_query' => [
            'after' => QUESTIONS_WRONG_TIMER
        ]
    ]);
    return $getAttempts->found_posts;
}
function questions_get_answer($post) {
    $user = wp_get_current_user();
    if (!is_user_logged_in() || !in_array('student', (array) $user->roles)) {
        return [
            'status'=>'error',
            'message'=>'Only students can answer a question'
        ];
    }
    $questionId = preg_replace('/\D/', '', sanitize_text_field($post['questionId']));
    $answer = sanitize_text_field($post['answer']);
    if (get_post_type($questionId) != 'question') {
        return [
            'status'=>'error',
            'message'=>'Invalid questionId: ' . $questionId
        ];
    }
    if (getAnsweredStatus($questionId)) {
        return ['status'=>'Done', 'message'=>'Done'];
    }
    if (getAttempts() >= QUESTIONS_MAX_WRONG) {
        return [
            'status'=>'error',
            'message'=>'Take a break, it is better to learn this material over time'
        ];
    }
    $getAnswer = new WP_Query([
        'post_type' => 'question',
        'p' => $questionId
    ]);
    /* $getAnswer->post_count == 1 */
    while($getAnswer->have_posts()) {
        $getAnswer->the_post();
        $correctAnswer = get_field('answer');
    }
    wp_reset_postdata();
    if (isset($correctAnswer) && $correctAnswer == $answer) {
        wp_insert_post([
            'post_type' => 'correct',
            'post_status' => 'publish',
            'meta_input' => [
                'question_id' => $questionId,
            ]
        ]);
        return ['status'=>'Correct', 'message'=>'test'];
    } else {
        wp_insert_post([
            'post_type' => 'wrong',
            'post_status' => 'publish',
            'meta_input' => [
                'question_id' => $questionId,
            ]
        ]);
        return ['status'=>'Wrong', 'message'=>'test'];
    }
}  /* end questions_get_answer($post) */

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

add_filter('template_include', 'lesson_taxonomy_template');
function calculateLessonDates($dataArray) {
    /* start_date is stored in parent lesson */
    $start = get_field('start_date', get_term($dataArray['lesson']->parent, 'lesson'));
    if ($dataArray['open'] == '') {
        $dataArray['open'] = 0; /* default */
    }
    if ($dataArray['due'] == '') {
        $dataArray['due'] = 52; /* default */
    }
    $now = new DateTime('now');
    $open = date_add(new DateTime($start),
        date_interval_create_from_date_string("{$dataArray['open']} weeks"));
    $due = date_add(new DateTime($start),
        date_interval_create_from_date_string("{$dataArray['due']} weeks"));
    if ($now >= $open && $now <= $due) {
        $status = 'open';
    } else {
        $status = 'close';
    }
    return [
        'open'=>$open,
        'due'=>$due,
        'status'=>$status,
    ];
}
function lesson_taxonomy_template($template) {
    $file_name = 'taxonomy-lesson.php';
    if (is_tax('lesson')) {
        if (locate_template($file_name)) {
            $template = locate_template($file_name);
        } else {
            $template = dirname(__FILE__) . '/' . $file_name;
        }
    }
    return $template;
}

add_action('pre_get_posts', 'reorder_question_for_lesson');
function reorder_question_for_lesson($query) {
    if(is_tax('lesson')) {
        $query->query_vars['orderby'] = 'meta_value_num';
        $query->query_vars['order'] = 'ASC';
    }
}
