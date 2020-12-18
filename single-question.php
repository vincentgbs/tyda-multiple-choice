<?php
/*
 * Template Name: Question Template
 */
$user = wp_get_current_user();
if (!is_user_logged_in() || !in_array('student', (array) $user->roles)) {
    die('Only students can view material <a href="' . site_url() . '/wp-login.php">Return to home</a>');
}

function getContinueUrl($dataArray) {
    $field = $dataArray['field'];
    $lessonName = $dataArray['lessonName'];
    $arrayOfQuestionIds = $dataArray['arrayOfQuestionIds'];
    if (get_field($field) == 'none') {
        return site_url('/archives/lessons/') . $lessonName;
    } else if (get_field($field) != '') {
        return site_url('/archives/question/') . get_field('next_question');
    } else {
        if ($field == 'next_question') {
            //
        } else if ($field == 'previous_question') {
            if ($indexOfThisQuestion - 1 < 0) {
                $continueQuestion = $arrayOfQuestionIds[count($arrayOfQuestionIds) - 1];
                $prevLink = site_url('/archives/question/') . $continueQuestion;
            } else {
                $continueQuestion = $arrayOfQuestionIds[($indexOfThisQuestion - 1) % count($arrayOfQuestionIds)];
                $prevLink = site_url('/archives/question/') . $continueQuestion;
            }
        }
    }
}

?>
<style>
body {
    background-color: LightCyan;
    font-family: Arial, Helvetica, sans-serif;
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

.question_options_list {
    list-style-type: none;
}
.question_option {
    background-color: MintCream;
    margin-left: -2.5rem;
    padding: 1rem;
    border-style: solid;
    border-width: thin;
    border-radius: 1rem;
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
.dull_link {
    text-decoration: none;
    color: black;
}

#questions_header {
    display: flex;
    height: 2.5rem;
}
#questions_remaining_container {
    width: 60%;
}
#attempts_remaining_container {
    width: 30%;
}
#close_container {
    min-with: 12.5%;
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

<?php
    $thisQuestionId = get_the_ID();
    $cramGuard = new WP_Query([
        'author' => get_current_user_id(),
        'post_type' => 'wrong',
        'date_query' => [
            'after' => QUESTIONS_WRONG_TIMER
        ]
    ]);
    wp_reset_postdata();
    $terms = get_the_terms($thisQuestionId, 'lesson');
    $lessonName = $terms[0]->slug;
    $questionInLesson = new WP_Query([
        'post_type' => 'question',
        'tax_query' => array(
            array(
                'taxonomy' => 'lesson',
                'field'    => 'slug',
                'terms'    => $lessonName,
            ),
        ),
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ]);
    $total = 0;
    $answered = 0;
    $questionCompleted = false;
    $tempIndex = 0;
    $arrayOfQuestionIds = []; /* holds all ids of question lesson in order */
    while($questionInLesson->have_posts()) {
        $questionInLesson->the_post();
        $total += 1;
        $thisQuestionAnswered = false;
        if (alreadyAnswered(get_the_ID())) {
            $answered += 1;
            $thisQuestionAnswered = true;
        }
        $tempQuestionId = get_the_ID();
        if ($tempQuestionId == $thisQuestionId) {
            if ($thisQuestionAnswered) {
                $questionCompleted = true; /* this question */
            }
            $indexOfThisQuestion = $tempIndex;
        }
        $arrayOfQuestionIds[] = $tempQuestionId;
        $tempIndex += 1;
    }
    wp_reset_postdata();
    while(have_posts()) {
        the_post();
?>

<div id="flashMessage"></div>
<div id="questions_body">
    <div id="questions_header">
        <input type="hidden" id="completed_questions_in_lesson"
            value="<?php echo $answered; ?>" />
        <input type="hidden" id="total_questions_in_lesson"
            value="<?php echo $total; ?>" />
        <input type="hidden" id="made_attempts"
            value="<?php echo $cramGuard->found_posts; ?>" />
        <input type="hidden" id="total_attempts"
            value="<?php echo QUESTIONS_MAX_WRONG; ?>" />
        <div id="questions_remaining_container">
            <a href="<?php echo site_url('/archives/lessons/') . $lessonName; ?>" class="dull_link"><div id="questions_remaining"></div></a>
            ↺
        </div>
        <div id="attempts_remaining_container"></div>
        <div id="close_container">
            <a href="#" class="dull_link"><button id="close">X</button></a>
        </div>
    </div>
    <div id="questions_content">
        <p><?php the_content(); ?></p>
    </div>
    <div id="questions_options">
        <ul class="question_options_list">
            <?php
                $keys = ['option1', 'option2', 'option3', 'answer'];
                shuffle($keys);
                foreach ($keys as $key=>$value) {
                    if(get_field($value)) {
            ?>
                    <li class="question_option<?php
                        if ($questionCompleted && $value == 'answer')
                            { echo ' correct'; }
                    ?>"><?php echo get_field($value); ?></li>
            <?php
                    }
                } /* end foreach */
            ?>
        </ul>
    </div>
    <div id="questions_footer">
        <?php
            if (get_field('next_question') == 'none') {
                $nextLink = site_url('/archives/lessons/') . $lessonName;
            } else if (get_field('next_question') != '') {
                $nextLink = site_url('/archives/question/') . get_field('next_question');
            } else {
                $nextQuestion = $arrayOfQuestionIds[($indexOfThisQuestion + 1) % count($arrayOfQuestionIds)];
                $nextLink = site_url('/archives/question/') . $nextQuestion;
            } /* end else(get_field('next_question') != '') */
            if (get_field('previous_question') == 'none') {
                $prevLink = site_url('/archives/lessons/') . $lessonName;
            } else if (get_field('previous_question') != '') {
                $prevLink = site_url('/archives/question/') . get_field('next_question');
            } else {
                if ($indexOfThisQuestion - 1 < 0) {
                    $lastQuestion = $arrayOfQuestionIds[count($arrayOfQuestionIds) - 1];
                    $prevLink = site_url('/archives/question/') . $lastQuestion;
                } else {
                    $lastQuestion = $arrayOfQuestionIds[$indexOfThisQuestion - 1];
                    $prevLink = site_url('/archives/question/') . $lastQuestion;
                }
            } /* end else(get_field('previous_question') != '') */
        } /* end while(have_posts()) */
        ?>
        <div class="continue_button"><a href="<?php echo $prevLink; ?>" class="dull_link"><div>⇦</div></a></div>
        <div class="continue_button"><a href="<?php echo $nextLink; ?>" class="dull_link"><div>⇨</div></a></div>
    </div>
</div>

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
        document.querySelector('#made_attempts').value = (1 + parseInt(document.querySelector('#made_attempts').value));
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
        for(let i=0; i<document.querySelector('#completed_questions_in_lesson').value; i++) {
            html += '<span>☑<span>';
        }
        for(let i=document.querySelector('#completed_questions_in_lesson').value; i<document.querySelector('#total_questions_in_lesson').value; i++) {
            html += '<span>□<span>';
        }
        display.innerHTML = html;
    },
    show_attempt_status: function() {
        let display = document.querySelector('#attempts_remaining_container');
        let html = '';
        for(let i=0; i<document.querySelector('#made_attempts').value; i++) {
            html += '<span>ⓧ<span>';
        }
        for(let i=document.querySelector('#made_attempts').value; i<document.querySelector('#total_attempts').value; i++) {
            html += '<span>ⓞ<span>';
        }
        display.innerHTML = html;
    },
}; /* end question var */

document.addEventListener("DOMContentLoaded", function() {
    console.debug('question.js loaded');
    /* Add Event Listeners */
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
