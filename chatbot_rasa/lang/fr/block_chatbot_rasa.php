<?php

/**
 * Strings for component 'chatbot_rasa', language 'fr'
 *
 * @package   chatbot_rasa
 * @copyright 2021 José-Paul Blülle Celado
 */

// Plugin
$string['pluginname'] = 'Chatbot Rasa';
$string['chatbot_rasa'] = 'Chatbot';
$string['chatbot_rasa:addinstance'] = 'Ajouter un nouveau chatbot block';
$string['chatbot_rasa:myaddinstance'] = 'Ajouter un nouveau block sur la page My Moodle.';

// Text
// subject
// response
// add response
// ...

// Error
$string['error_spam'] = 'Trop de messages envoyés, veuillez attendre {$a->seconds} secondes.';
$string['error_max_length_string'] = 'La chaîne de caractères est trop longue, maximum de {$a->maxlength}.';
$string['error_empty_string'] = 'La chaîne de caractères ne doit pas être vide.';

$string['error_no_authorization_course_view'] = 'Vous n\'avez pas l\'autorisation de voir le cours actuel ou il n\'existe pas.';
$string['error_no_authorization_course_edit'] = 'Vous n\'avez pas l\'autorisation de modifier le cours actuel ou il n\'existe pas.';
$string['error_no_authorization_course_edit_transfer_from'] = 'Vous n\'avez pas l\'autorisation de modifier le cours à transférer ou il n\'existe pas.';

$string['error_transfer_question_courseid_same'] = 'Le cours à transférer est le même que celui-ci.';
$string['error_transfer_question_courseid_min'] = 'L\'id du cours ne doit pas être inférieur à 2.';
$string['error_transfer_question_courseid_not_number'] = 'L\'input "{$a->courseid}" n\'est pas un chiffre.';

$string['error_change_or_delete_no_selection'] = 'Veuillez sélectionner une question.';

$string['error_course_id_not_present'] = 'L\id du cours "{$a->courseid}" n\'existe pas.';
$string['error_not_listed'] = 'Erreur interne, veuillez contacter un administrateur.';

// Confirmation
$string['confirmation_create_question'] = 'Êtes-vous certain de vouloir créer cette question ?';
$string['confirmation_transfer_question'] = 'Êtes-vous certain de vouloir déplacer les questions du cours avec l\'id "{$a->oldcourseid}" à celui-ci ? (Cela supprimera toutes les questions à l\'ancien !)';

// Validation
$string['validation_question_saved'] = 'La question a été enregistrée.';
$string['validation_transfer_question_executed'] = 'Le transfert a été exécuté.';
$string['validation_question_deleted_all'] = 'Toutes les questions du cours ont été supprimées.';
$string['validation_question_deleted'] = 'Question supprimée.';
$string['validation_question_modified'] = 'Question modifiée.';

// Event
$string['event_name_message_sent'] = 'Message envoyé';
$string['event_description_message_sent'] = 'à faire';

$string['event_name_question_created'] = 'Question créée';
$string['event_description_question_created'] = 'à faire';

$string['event_name_question_deleted'] = 'Question supprimée';
$string['event_description_question_deleted'] = 'à faire';

$string['event_name_question_modified'] = 'Question modifiée';
$string['event_description_question_modified'] = 'à faire';

$string['event_name_question_transfered'] = 'Question transférée';
$string['event_description_question_transfered'] = 'à faire';
?>
