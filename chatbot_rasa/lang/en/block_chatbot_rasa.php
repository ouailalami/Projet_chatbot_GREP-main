<?php

/**
 * Strings for component 'chatbot_rasa', language 'en'
 *
 * @package   chatbot_rasa
 * @copyright 2021 José-Paul Blülle Celado
 */

// Plugin
$string['pluginname'] = 'Chatbot Rasa';
$string['chatbot_rasa'] = 'Chatbot';
$string['chatbot_rasa:addinstance'] = 'Add a new chatbot block';
$string['chatbot_rasa:myaddinstance'] = 'Add a new chatbot block to My Moodle page';

// Error
$string['error_spam'] = 'Too many messages sent, please wait {$a->seconds} seconds.';
$string['error_max_length_string'] = 'The character string is too long, maximum is {$a->maxlength}.';
$string['error_empty_string'] = 'The character string must not be empty.';

$string['error_no_authorization_course_view'] = 'You don\'t have permission to view the current course or it doesn\'t exist.';
$string['error_no_authorization_course_edit'] = 'You don\'t have permission to edit the current course or it doesn\'t exist.';
$string['error_no_authorization_course_edit_transfer_from'] = 'You don\'t have permission to modify the course to transfer or it doesn\'t exist.';

$string['error_transfer_question_courseid_same'] = 'The course to be transferred is the same as this one.';
$string['error_transfer_question_courseid_min'] = 'The course id must not be less than 2.';
$string['error_transfer_question_courseid_not_number'] = 'The input "{$a->courseid}" is not a number.';

$string['error_change_or_delete_no_selection'] = 'Please select a question.';

$string['error_course_id_not_present'] = 'The course id "{$a->courseid}" don\'t exist.';
$string['error_not_listed'] = 'Internal error, please contact an administrator.';

// Confirmation
$string['confirmation_create_question'] = 'Are you sure you want to create this question ?';
$string['confirmation_transfer_question'] = 'Are you sure you want to move the course\'s questions with id "{$a->oldcourseid}" to this one ? (This removes all questions from the old course !)';

// Validation
$string['validation_question_saved'] = 'The question has been saved.';
$string['validation_transfer_question_executed'] = 'The transfer has been completed.';
$string['validation_question_deleted_all'] = 'All course questions have been removed.';
$string['validation_question_deleted'] = 'Question deleted.';
$string['validation_question_modified'] = 'Question modified.';

// Event
$string['event_name_message_sent'] = 'Message sent';
$string['event_description_message_sent'] = 'todo';

$string['event_name_question_created'] = 'Question created';
$string['event_description_question_created'] = 'todo';

$string['event_name_question_deleted'] = 'Question deleted';
$string['event_description_question_deleted'] = 'todo';

$string['event_name_question_modified'] = 'Question modified';
$string['event_description_question_modified'] = 'todo';

$string['event_name_question_transfered'] = 'Question transfered';
$string['event_description_question_transfered'] = 'todo';
?>
