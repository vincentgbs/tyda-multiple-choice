<?php
/*
 * Template Name: Lesson Question
 */
$user = wp_get_current_user();
if (!is_user_logged_in() || !in_array('student', (array)$user->roles)) {
    die('Only students can view the material <br />
        <a href="' . site_url() . '/wp-login.php">Login</a>');
}

function getLessonData($dataArray) {
    $totalQuestions = 0;
    $completedQuestions = 0;
    $thisQuestionCompleted = false;
    $questionsInLesson = new WP_Query([
        'post_type' => 'question',
        'tax_query' => array(
            array(
                'taxonomy' => 'lesson',
                'field'    => 'slug',
                'terms'    => $dataArray['lessonSlug'],
            ),
        ),
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ]);
    $tempIndex = 0;
    $lessonQuestionIds = []; /* array of question ids in lesson */
    while($questionsInLesson->have_posts()) {
        $questionsInLesson->the_post();
        $totalQuestions += 1;
        $thisQuestionAnswered = false;
        if (alreadyAnswered(get_the_ID())) {
            $completedQuestions += 1;
            $thisQuestionAnswered = true;
        }
        $tempQuestionId = get_the_ID();
        $lessonQuestionIds[] = $tempQuestionId;
        if ($tempQuestionId == $dataArray['thisQuestionId']) {
            if ($thisQuestionAnswered) {
                $thisQuestionCompleted = true;
            }
            $indexOfThisQuestion = $tempIndex;
        }
        $tempIndex += 1;
    }
    wp_reset_postdata();
    return [
        'totalQuestions'=>$totalQuestions,
        'completedQuestions'=>$completedQuestions,
        'thisQuestionCompleted'=>$thisQuestionCompleted,
        'lessonQuestionIds'=>$lessonQuestionIds,
        'indexOfThisQuestion'=>$indexOfThisQuestion,
    ];
}

function getContinueUrl($dataArray) {
    $qIds = $dataArray['arrayOfQuestionIds'];
    $qIndex = $dataArray['indexOfThisQuestion'];
    if (get_field($dataArray['field']) == 'none') {
        return site_url('/archives/lessons/') . $dataArray['lessonName'];
    } else if (get_field($dataArray['field']) != '') {
        return site_url('/archives/question/') . get_field($dataArray['field']);
    } else {
        if ($dataArray['field'] == 'previous_question') {
            $continueQuestionIndex = (count($qIds) + $qIndex - 1) % count($qIds);
            $continueQuestion = $qIds[$continueQuestionIndex];
            return site_url('/archives/question/') . $continueQuestion;
        } else if ($dataArray['field'] == 'next_question') {
            $continueQuestionIndex = ($qIndex + 1) % count($qIds);
            $continueQuestion = $qIds[$continueQuestionIndex];
            return site_url('/archives/question/') . $continueQuestion;
        } else {
            return '#';
        }
    }
}

$thisQuestionId = get_the_ID();
$lessonTaxonomy = get_the_terms($thisQuestionId, 'lesson');
if (isset($lessonTaxonomy[0]) && isset($lessonTaxonomy[0]->slug)) {
    $lessonSlug = $lessonTaxonomy[0]->slug;
} else {
    $lessonSlug = '#';
}
$lessonData = getLessonData([
    'lessonSlug'=>$lessonSlug,
    'thisQuestionId'=>$thisQuestionId,
]);
while(have_posts()) {
    the_post();
    $keys = ['option1', 'option2', 'option3', 'answer'];
    shuffle($keys);
    $nextLink = getContinueUrl([
        'field'=>'next_question',
        'lessonName'=>$lessonSlug,
        'arrayOfQuestionIds'=>$lessonData['lessonQuestionIds'],
        'indexOfThisQuestion'=>$lessonData['indexOfThisQuestion'],
    ]);
    $prevLink = getContinueUrl([
        'field'=>'previous_question',
        'lessonName'=>$lessonSlug,
        'arrayOfQuestionIds'=>$lessonData['lessonQuestionIds'],
        'indexOfThisQuestion'=>$lessonData['indexOfThisQuestion'],
    ]);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo ucfirst($lessonSlug); ?></title>
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
    .correct {
        background-color: MediumSeaGreen;
    }
    .wrong {
        background-color: IndianRed;
    }
    .continue_button {
        background-color: MediumTurquoise;
        width: 49%;
        height: 2.5rem;
        border-style: solid;
        border-width: thin;
        border-radius: 1rem;
        text-align: center;
        padding-top: 1rem;
    }

    #flashMessage {
        text-align: center;
        font-size: 2rem;
        position: fixed;
        z-index: 2;
        display: none;
        min-width: 50%;
        height: 3rem;
        top: 10%;
        left: 10%;
        background-color: rgba(0,0,0,0.3);
    }
    #questions_header {
        min-height: 2.5rem;
    }
    #questions_header_top_row {
        height: 2.5rem;
        display: flex;
    }
    #questions_header_bottom_row {
        padding: 0 2.5rem;
    }
    #lesson_name_container {
        margin-left: 5rem;
        width: 60%;
    }
    #attempts_remaining_container {
        width: 30%;
    }
    #attempts_remaining_container span {
        float: right;
    }
    #close_container {
        min-width: 12.5%;
    }
    #close {
        float: right;
        background-color: MediumTurquoise;
        border-radius: 2.5rem;
        height: 2.5rem;
        width: 2.5rem;
    }
    #questions_content {
        background-color: MintCream;
        border-style: inset;
        border-width: thin;
        border-radius: 1rem;
        min-height: 7.5rem;
    }
    #questions_footer {
        display: flex;
    }
    </style>
</head>
<div id="flashMessage"></div>
<div id="questions_body">
    <div id="questions_header">
        <div id="questions_header_top_row">
            <input type="hidden" id="completed_questions"
                value="<?php echo $lessonData['completedQuestions']; ?>" />
            <input type="hidden" id="total_questions"
                value="<?php echo $lessonData['totalQuestions']; ?>" />
            <input type="hidden" id="attempts_made"
                value="<?php echo getAttempts(); ?>" />
            <input type="hidden" id="total_attempts_allowed"
                value="<?php echo QUESTIONS_MAX_WRONG; ?>" />
            <div id="lesson_name_container"><?php echo ucfirst($lessonSlug);
            ?></div>
            <div id="attempts_remaining_container"></div>
            <div id="close_container">
                <a href="<?php echo site_url('/archives/lessons/') . $lessonSlug; ?>"
                    class="dull_link"><button id="close">✖</button></a>
            </div>
        </div>
        <div id="questions_header_bottom_row">
            <div id="questions_remaining"></div>
        </div>
    </div>
    <div id="questions_content">
        <p><?php the_content(); ?></p>
    </div>
    <div id="questions_options">
        <div>
            <?php
                foreach ($keys as $key=>$value) {
                    if(get_field($value)) {
            ?>
                    <div class="question_option<?php
                        if ($lessonData['thisQuestionCompleted'] && $value == 'answer')
                            { echo ' correct'; }
                    ?>"><?php echo get_field($value); ?></div>
            <?php
                    }
                } /* end foreach */
            ?>
        </div>
    </div>
    <div id="questions_footer">
        <div class="continue_button"><a href="<?php echo $prevLink; ?>" class="dull_link"><div>⇦</div></a></div>
        <div class="continue_button"><a href="<?php echo $nextLink; ?>" class="dull_link"><div>⇨</div></a></div>
    </div>
<?php } /* end while(have_posts()) */ ?>
</div> <!-- <div id="questions_body"> -->
</body>
<script>
var question = {
    encodeJsonData: function(object) {
        let array = [];
        Object.keys(object).forEach(key =>
            array.push(
                encodeURIComponent(key) + "=" + encodeURIComponent(object[key])
            )
        );
        return array.join("&");
    }, /* end encodeJsonData() */
    flashMessage: function(message, timer=2500) {
        if (document.querySelector("#flashMessage")) {
            let div = document.querySelector("#flashMessage");
            div.innerText = message;
            div.style.display = 'block';
            setTimeout(function() {
                div.innerText = '';
                div.style.display = 'none';
            }, timer);
        }
    }, /* end flashMessage() */
    answerQuestion: function(answer) {
        if (question.pause) {
            question.flashMessage('loading', 999);
        } else {
            question.pause = true;
            let xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo get_site_url(); ?>' + '/wp-json/university/v1/answer');
            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                try {
                    let response = JSON.parse(xhr.response);
                    if (response['status'] == 'Done') {
                        console.log('Done'); // continue
                    } else if (response['status'] == 'Correct') {
                        answer.classList.add('correct');
                        // question.incrementQuestionsAnswered();
                    } else if (response['status'] == 'Wrong') {
                        answer.classList.add('wrong');
                        question.decrementAttemptsRemaining();
                    } else {
                        question.flashMessage(response['message'], 9999);
                    }
                } catch {
                    console.debug(xhr.response);
                }
            }; /* end xhr.onload */
            try {
                let request = question.encodeJsonData({
                    questionId: <?php echo get_the_ID(); ?>,
                    answer: answer.innerText
                });
                xhr.send(request);
            } catch (err) {
                question.flashMessage(err, 9999);
            }
        }
    }, /* end answerQuestion() */
    pause: false,
    decrementAttemptsRemaining: function() {
        let attempts = document.querySelector('#attempts_remaining_container');
        document.querySelector('#attempts_made').value = (1 + parseInt(document.querySelector('#attempts_made').value));
        attempts.style.color = 'red';
        setTimeout(function() {
            question.show_attempt_status();
            setTimeout(function() {
                question.pause = false;
                attempts.style.color = 'black';
            }, 999);
        }, 999);
    }, /* end decrementAttemptsRemaining() */
    show_question_status: function() {
        let display = document.querySelector('#questions_remaining');
        let html = '';
        for(let i=0; i<document.querySelector('#completed_questions').value; i++) {
            html += '<span>☑<span>';
        }
        for(let i=document.querySelector('#completed_questions').value; i<document.querySelector('#total_questions').value; i++) {
            html += '<span>□<span>';
        }
        display.innerHTML = html;
    },
    show_attempt_status: function() {
        let display = document.querySelector('#attempts_remaining_container');
        let html = '';
        for(let i=0; i<document.querySelector('#attempts_made').value; i++) {
            html += '<span>ⓧ<span>';
        }
        for(let i=document.querySelector('#attempts_made').value; i<document.querySelector('#total_attempts_allowed').value; i++) {
            html += '<span>ⓞ<span>';
        }
        display.innerHTML = html;
    },
}; /* end question var */

document.addEventListener("DOMContentLoaded", function() {
    let options = document.querySelectorAll('.question_option');
    Array.from(options).forEach(function(option) {
        option.addEventListener('click', function() {
            question.answerQuestion(this);
        });
    });
    question.show_question_status();
    question.show_attempt_status();
});
</script>
</html>
