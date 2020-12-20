A simple Lesson Building Plugin for Wordpress

The 'question' post needs subfields: 'answer', 'option1', 'option2', 'option3', 'next_question', 'previous_question'. The 'answers' field must be populated, while the others are optional.
Optionally, you can add the 'question_id' to the 'correct' and 'wrong' post types to track them in the backend.

The 'lesson' taxonomy needs the subfields: 'week_open', 'week_due', 'start_date'. These correspond to the dates that the lesson will be available with respect to the semester start. The 'start_date' value is expected to be contained in the lesson parent (hierarchical taxonomy).
The members plugin is needed to add the 'student', 'alumni', 'teacher' roles.
