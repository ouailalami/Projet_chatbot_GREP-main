<?php
/**
 * {@link https://docs.moodle.org/dev/Coding_style}
 * {@link https://docs.moodle.org/dev/Coding_style#Variables}
 *
 * {@link https://docs.moodle.org/dev/External_functions_API}
 * {@link https://docs.moodle.org/dev/Adding_a_web_service_to_a_plugin}
 * {@link https://mahakala.lesc.uec.ac.jp/mahoodle/moodle/docs/34/ja/dev/Adding_a_web_service_to_a_plugin.html}
 * {@link https://docs.moodle.org/dev/External_services_description}
 *
 * {@link https://docs.moodle.org/dev/Exceptions}
 */
// https://docs.moodle.org/dev/Talk:External_services_description
/**
 * Classe contenant les services appelés par le block chatbot_rasa
 * en AJAX et une fonction 'get_question' concue pour
 * être appeler de l'exterieure par le serveur Rasa.
 *
 * @package   chatbot_rasa
 * @copyright 2021 José-Paul Blülle Celado
 * @license   ?
 */

// TODO : Donner à chaque message le courseid à Rasa (mais pas si courseid == 1) !
// TODO : Définir correctement les valeurs de paramètres et de retours.
// TODO : Implémenter où il y en a besoin des "print_error". (par exemple si l'utilisateur ne devrait pas être dans un cours -> mais déjà fait par Moodle ?)

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/config.php");

define('TABLE_QUESTION', 'block_chatbot_rasa_question');
define('TABLE_INTERACT', 'block_chatbot_rasa_interact');

class moodle_external_chatbot_rasa extends external_api {
    /**
     * Test si l'utilisateur est en train de SPAM.
     *
     * @return void
     * @throws RuntimeException
     */
    private static function test_spam() {
        global $SESSION;

        $delay = 60;
        $maxmessageindelay = 15;
        $timenow = microtime(true);

        if (!isset($SESSION->time) || !isset($SESSION->nbmessage)) {
            $SESSION->time = microtime(true) + $delay;
            $SESSION->nbmessage = 0;
        }
        if ($timenow < $SESSION->time) {
            if ($SESSION->nbmessage >= $maxmessageindelay) {
                throw new RuntimeException(get_string('error_spam', 'block_chatbot_rasa', ['time' => ceil($SESSION->time - $timenow)]), 231);

            } else {
                $SESSION->nbmessage += 1;
            }

        } else {
            $SESSION->nbmessage = 1;
            $SESSION->time = microtime(true) + $delay;
        }
    }

    /**
     * Test l'input de l'utilisateur.
     *
     * @param string $texttrimmed L'input de l'utilisateur trim().
     * @return void
     * @throws RuntimeException
     */
    private static function test_input(string $texttrimmed) {
        $textwithoutspace = preg_replace('/\s+/', '', $texttrimmed);
        $maxlength = 255;

        if (strlen($textwithoutspace) == 0) {
            throw new RuntimeException(get_string('error_empty_string', 'block_chatbot_rasa'), 232);

        } else if (strlen($texttrimmed) > $maxlength) {
            throw new RuntimeException(get_string('error_max_length_string', 'block_chatbot_rasa', ['maxlength' => $maxlength]), 233);

        } /*else if (strlen($textwithoutspace) < 5) {
            throw new RuntimeException('Nombre de caractères insuffisants (5 minimum).', 234);
        } */
    }

    /**
     * Définit les paramètres utilisés par la fonction 'post_message'.
     *
     * @see post_message()
     * @return external_function_parameters
     */
    public static function post_message_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'text'     => new external_value(PARAM_TEXT, 'text from user')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'post_message'.
     *
     * @see post_message()
     * @return external_value
     */
    public static function post_message_returns() {
        return new external_value(PARAM_TEXT, 'response from Rasa');
    }

    /**
     * Renvoi en JSON la réponse à la question de l'utilisateur
     * par l'intérmédiaire de Rasa.
     *
     * @param int     $courseid L'id du cours.
     * @param string  $text L'input de l'utilisateur.
     * @return string La réponse du chatbot Rasa.
     * @throws RuntimeException
     */
    public static function post_message($courseid, $text) {
        global $DB, $USER;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::post_message_parameters(), array(
            'courseid' => $courseid,
            'text' => $text
        ));
        $courseid = $params['courseid'];
        $text = mb_strtolower($params['text'], 'UTF-8');

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/block:view', $coursecontext);

        } catch (Exception $ex) { // Ne devrait jamais arriver ici normalment.
            //print_error();
            return '{"error": "' . get_string('error_no_authorization_course_view', 'block_chatbot_rasa') . '"}';
        }

        try {
            self::test_spam();
            $texttrimmed = trim(preg_replace('/\s\s+/', ' ', str_replace('\n', ' ', $text)));
            self::test_input($texttrimmed);

            // Définit le slot 'is_in_cours' pour prévenir si on est dans un cours ou l'accueil.
            $urlservice = 'http://localhost:5005/conversations/' . $USER->id . '/tracker/events?include_events=NONE';
            $curlpostdata = json_encode(array(
                'event' => 'slot',
                'name' => 'is_in_cours',
                'value' => ($courseid > 1),
                'timestamp' => (new DateTime())->getTimestamp()
            ));
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlservice);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpostdata);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = curl_exec($ch);
            curl_close($ch);

            // L'envoi du message.
            $urlservice = 'http://localhost:5005/webhooks/rest/webhook';
            $curlpostdata = json_encode(array(
                'sender' => $USER->id,
                'message' => $texttrimmed //. '/{{"course_module_number":"' . $courseid . '"}}'
                // Nécessaire d'indiquer l'id du cours à chaque message (sauf si == 1) -> à faire fonctionner.
            ));
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlservice);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpostdata);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $response = json_decode(curl_exec($ch))[0];
            curl_close($ch);

            // https://stackoverflow.com/questions/31121661/insert-into-database-moodle
            $record = new StdClass();
            $record->user_id = $USER->id;
            $record->course_id = $courseid;
            $record->question = $texttrimmed;
            if (isset($response->custom->cannot_respond)) { // Rasa n'arrive pas à répondre.
                $record->response = $response->custom->text;
                $record->can_respond = 0;
                $response->text = $response->custom->text;

            } else {
                $choices = isset($response->buttons) ? implode(',', array_column($response->buttons, 'title')) : '';
                $record->response = $response->text . $choices; // TODO : Rasa doit donner l'utter !
                $record->can_respond = 1;
            }
            $record->date = (new DateTime())->getTimestamp(); // time() fait la même chose ?
            $response->id = $DB->insert_record(TABLE_INTERACT, $record);

            // Logue l'envoi du message.
            /*$event = \block_chatbot_rasa\event\message_sent::create(array(
                'objectid' => $record->id,
                'context'  => $coursecontext
            ));
            $event->trigger();*/
            return json_encode($response); // TODO : Modifier la fonction qui défini le retour (pour un parse automatique) !

        } catch (RuntimeException $ex) {
            if ($ex->getCode() < 240 && $ex->getCode() > 230) {
                return '{"error": "' . $ex->getMessage() . '"}';

            } else {
                throw ex;
                //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
            }
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'create_question'.
     *
     * @see create_question()
     * @return external_function_parameters
     */
    public static function create_question_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'subject'  => new external_value(PARAM_TEXT, 'subject'),
                'question' => new external_value(PARAM_TEXT, 'question'),
                'response' => new external_value(PARAM_TEXT, 'response')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'create_question'.
     *
     * @see create_question()
     * @return external_value
     */
    public static function create_question_returns() {
        return new external_value(PARAM_TEXT, 'validation of the question creation');
    }

    /**
     * Ecrit la question en paramètre dans la base de données pour Rasa.
     *
     * @param int     $courseid L'id du cours.
     * @param string  $subject Le sujet en rapport avec la question.
     * @param string  $question La question.
     * @param string  $response La réponse à la question.
     * @return string Un objet avec un champs 'error' ou simple valeur '1'.
     * @throws RuntimeException
     */
    public static function create_question($courseid, $subject, $question, $response) {
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::create_question_parameters(), array(
            'courseid' => $courseid,
            'subject' => $subject,
            'question' => $question,
            'response' => $response
        ));
        $courseid = $params['courseid'];
        $subject = $params['subject'];
        $question = $params['question'];
        $response = $params['response'];

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/site:manageblocks', $coursecontext);

        } catch (Exception $ex) {
            //print_error();
            return '{"error": "' . get_string('error_no_authorization_course_edit', 'block_chatbot_rasa') . '"}';
        }

        try {
            $record = new StdClass();
            $record->course_id = $courseid;
            $record->subject = $subject;
            $record->question = $question;
            $record->response = $response;
            $record->id = $DB->insert_record(TABLE_QUESTION, $record);

            // Logue la création d'une question.
            $event = \block_chatbot_rasa\event\question_created::create(array(
                'objectid' => $record->id,
                'context'  => $coursecontext
            ));
            $event->trigger();
            return $record->id;

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'modify_question'.
     *
     * @see modify_question()
     * @return external_function_parameters
     */
     public static function modify_question_parameters() {
         return new external_function_parameters(
             array(
                 'courseid' => new external_value(PARAM_INT, 'course id'),
                 'id'       => new external_value(PARAM_INT, 'question id'),
                 'subject'  => new external_value(PARAM_TEXT, 'subject'),
                 'question' => new external_value(PARAM_TEXT, 'question'),
                 'response' => new external_value(PARAM_TEXT, 'response')
             )
         );
     }

    /**
     * Définit ce que retourne la fonction 'modify_question'.
     *
     * @see modify_question()
     * @return external_value
     */
    public static function modify_question_returns() {
        return new external_value(PARAM_TEXT, 'validation of the question modification');
    }

    /**
     * Modifie la question en paramètre dans la base de données.
     *
     * @param int     $courseid L'id du cours.
     * @param int     $id L'id de la question.
     * @param string  $subject Le sujet en rapport avec la question.
     * @param string  $question La question.
     * @param string  $response La réponse à la question.
     * @return string Un objet avec un champs 'error' ou simple valeur '1'.
     * @throws RuntimeException
     */
    public static function modify_question($courseid, $id, $subject, $question, $response) {
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::modify_question_parameters(), array(
            'courseid' => $courseid,
            'id' => $id,
            'subject' => $subject,
            'question' => $question,
            'response' => $response
        ));
        $courseid = $params['courseid'];
        $id = $params['id'];
        $subject = $params['subject'];
        $question = $params['question'];
        $response = $params['response'];

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/site:manageblocks', $coursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_edit', 'block_chatbot_rasa') . '"}';
        }

        try {
            if ($DB->record_exists(TABLE_QUESTION, array('id' => $id, 'course_id' => $courseid))) {
                $record = new StdClass();
                $record->id = $id;
                $record->course_id = $courseid;
                $record->subject = $subject;
                $record->question = $question;
                $record->response = $response;
                $DB->update_record(TABLE_QUESTION, $record);

                $event = \block_chatbot_rasa\event\question_modified::create(array(
                    'objectid' => $record->id,
                    'context'  => $coursecontext
                ));
                $event->trigger();
                return '1';

            } else {
                throw new RuntimeException(); // La question a été supprimée, ou l'id a été modifié par l'utilisateur.
            }

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'delete_question'.
     *
     * @see delete_question()
     * @return external_function_parameters
     */
    public static function delete_question_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'id'       => new external_value(PARAM_INT, 'question id')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'delete_question'.
     *
     * @see delete_question()
     * @return external_value
     */
    public static function delete_question_returns() {
        return new external_value(PARAM_TEXT, 'validation of the question deletion');
    }

    /**
     * Supprime la question en paramètre dans la base de données.
     * Si l'id est égale à '-2' supprime toutes les instances avec
     * l'id du cours.
     *
     * @param int     $courseid L'id du cours.
     * @param int     $id L'id de la question.
     * @return string Un objet avec un champs 'error' ou simple valeur '1'.
     * @throws RuntimeException
     */
    public static function delete_question($courseid, $id) {
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::delete_question_parameters(), array(
            'courseid' => $courseid,
            'id' => $id
        ));
        $courseid = $params['courseid'];
        $id = $params['id'];

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/site:manageblocks', $coursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_edit', 'block_chatbot_rasa') . '"}';
        }

        try {
            $record = new StdClass();
            if ($id == -2) {
                $res = $DB->delete_records(TABLE_QUESTION, array('course_id' => $courseid));
                $eventparams = array('context' => $coursecontext);

            } else {
                $res = $DB->delete_records(TABLE_QUESTION, array('id' => $id, 'course_id' => $courseid));
                $eventparams = array('objectid' => $id, 'context' => $coursecontext);
            }

            if ($res) {
                // Logue la suppression d'une question.
                $event = \block_chatbot_rasa\event\question_deleted::create($eventparams);
                $event->trigger();
                return '1';

            } else {
                return '{"error": "' . get_string('error_course_id_not_present', 'block_chatbot_rasa', ['courseid' => $id]) . '"}';
            }

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'get_question'.
     *
     * @see get_question()
     * @return external_function_parameters
     */
    public static function get_question_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id')/*,
                'subject'  => new external_value(PARAM_TEXT, 'subject'),
                'question' => new external_value(PARAM_TEXT, 'question')*/
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'get_question'.
     *
     * @see get_question()
     * @return external_value
     */
    public static function get_question_returns() {
        return new external_value(PARAM_TEXT, 'the questions\'s list');
        /*return new external_multiple_structure(
            array(
                new external_value(PARAM_INT, 'question id'),
                new external_value(PARAM_TEXT, 'subject'),
                new external_value(PARAM_TEXT, 'question'),
                new external_value(PARAM_TEXT, 'response')
            )
        );*/
    }

    /**
     * Récupère les questions de la base de données pour un cours
     * pour modification / suppression.
     *
     * @param int     $courseid L'id du cours.
     * @return string Une ou plusieurs instances de la base de données.
     * @throws RuntimeException
     */
    public static function get_question($courseid) {
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::get_question_parameters(), array(
            'courseid' => $courseid
        ));
        $courseid = $params['courseid'];
        $return = array();

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/site:manageblocks', $coursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_edit', 'block_chatbot_rasa') . '"}';
        }

        try {
            $records = $DB->get_recordset_select(TABLE_QUESTION, "course_id = ?", array($courseid), '', 'id, subject, question, response');
            foreach ($records as $record) {
                $return[] = $record;
            }
            return json_encode($return);

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'get_question_rasa'.
     *
     * @see get_question_rasa()
     * @return external_function_parameters
     */
    public static function get_question_rasa_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'subject'  => new external_value(PARAM_TEXT, 'subject'),
                'question' => new external_value(PARAM_TEXT, 'question')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'get_question_rasa'.
     *
     * @see get_question_rasa()
     * @return external_multiple_structure
     */
    public static function get_question_rasa_returns() {
        return new external_multiple_structure(
            new external_value(PARAM_RAW, 'Can be multiple subjects, questions or a single response')
        );
    }

    /**
     * Récupère un ou plusieurs question de la base de données pour Rasa.
     *
     * @param int     $courseid L'id du cours.
     * @param string  $subject Le sujet en rapport avec la question.
     * @param string  $question La question.
     * @return string Un ou plusieurs questions de la base de données.
     * @throws RuntimeException
     */
    public static function get_question_rasa($courseid, $subject, $question) { // Catch pour ne pas envoyer une erreur à Rasa ou Rasa vérifie ?
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::get_question_rasa_parameters(), array(
            'courseid' => $courseid,
            'subject' => $subject,
            'question' => $question
        ));
        $courseid = $params['courseid'];
        $subject = mb_strtolower($params['subject'], 'UTF-8');
        $question = $params['question'];

        // Valide le contexte.
        $systemcontext = context_system::instance();
        external_api::validate_context($systemcontext);
        require_capability('webservice/rest:use', $systemcontext);

        try {
            if ($subject == '') {
                $records = $DB->get_fieldset_select(TABLE_QUESTION, 'subject', "course_id = ?", array($courseid));

            } else if ($question == '') {
                $records = $DB->get_fieldset_select(TABLE_QUESTION, 'question', "course_id = ? AND subject = ?", array($courseid, $subject));

            } else {
                $records = $DB->get_fieldset_select(TABLE_QUESTION, 'response', "course_id = ? AND subject = ? AND question = ?", array($courseid, $subject, $question));
            }
            return $records;

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'trigger_welcome'.
     *
     * @see trigger_welcome()
     * @return external_function_parameters
     */
    public static function trigger_welcome_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'trigger_welcome'.
     *
     * @see trigger_welcome()
     * @return external_value
     */
    public static function trigger_welcome_returns() {
        return new external_value(PARAM_TEXT, 'welcome message');
    }

    /**
     * Renvoi en JSON la réponse à la question de l'utilisateur
     * par l'intérmédiaire de Rasa.
     *
     * @param int     $courseid L'id du cours.
     * @return string Le message de salutation.
     * @throws RuntimeException
     */
    public static function trigger_welcome($courseid) {
        global $DB, $USER;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::trigger_welcome_parameters(), array(
            'courseid' => $courseid
        ));
        $courseid = $params['courseid'];

        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/block:view', $coursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_view', 'block_chatbot_rasa') . '"}';
        }

        try {
            $ch = curl_init();
            $urlservice = 'http://localhost:5005/webhooks/rest/webhook';
            // Posibilité d'avoir une salutation pour enseignants / étudiants ?
            $curlpostdata = json_encode(array(
                'sender' => $USER->id,
                'message' => (($courseid == 1) ? '/welcome_intent_trigger' : '/cours_intent_trigger{"course_module_number":"' . $courseid . '"}')
            ));
            curl_setopt($ch, CURLOPT_URL, $urlservice);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpostdata);
            $response = json_decode(curl_exec($ch))[0];
            curl_close($ch);

            if ($response == null) {
                throw new RuntimeException();
            }
            return json_encode($response);

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'transfer_old_course'.
     *
     * @see transfer_old_course()
     * @return external_function_parameters
     */
    public static function transfer_old_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid'    => new external_value(PARAM_INT, 'course id'),
                'oldcourseid' => new external_value(PARAM_INT, 'old course id')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'transfer_old_course'.
     *
     * @see transfer_old_course()
     * @return external_value
     */
    public static function transfer_old_course_returns() {
        return new external_value(PARAM_TEXT, 'validation of the courseid udpate');
    }

    /**
     * Ecrit la question en paramètre dans la base de données pour Rasa.
     *
     * @param int     $courseid L'id du cours.
     * @param int     $oldcourseid L'id du cours aux question à transférer.
     * @return string Un objet avec un champs 'error' ou une simple valeur '1'.
     * @throws RuntimeException
     */
    public static function transfer_old_course($courseid, $oldcourseid) {
        global $DB;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::transfer_old_course_parameters(), array(
            'courseid' => $courseid,
            'oldcourseid' => $oldcourseid
        ));
        $courseid = $params['courseid'];
        $oldcourseid = $params['oldcourseid'];

        if ($courseid < 2 || $oldcourseid < 2) {
            return '{"error": "' . get_string('error_transfer_question_courseid_min', 'block_chatbot_rasa') . '"}';

        } else if ($courseid == $oldcourseid) {
            return '{"error": "' . get_string('error_transfer_question_courseid_same', 'block_chatbot_rasa') . '"}';
        }

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/site:manageblocks', $coursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_edit', 'block_chatbot_rasa') . '"}';
        }
        try {
            $oldcoursecontext = context_course::instance($oldcourseid, MUST_EXIST);
            external_api::validate_context($oldcoursecontext);
            require_capability('moodle/site:manageblocks', $oldcoursecontext);

        } catch (Exception $ex) {
            return '{"error": "' . get_string('error_no_authorization_course_edit_transfer_from', 'block_chatbot_rasa') . '"}';
        }

        try {
            // https://docs.moodle.org/dev/SQL_coding_style
            $sql = "UPDATE {block_chatbot_rasa_question}
                       SET course_id = :courseid
                     WHERE course_id = :oldcourseid";
            $DB->execute($sql, array('courseid' => $courseid, 'oldcourseid' => $oldcourseid));

            $event = \block_chatbot_rasa\event\question_transfered::create(array(
                'context' => $coursecontext,
                'other'   => array(
                    'oldcourseid' => $oldcourseid
                )
            ));
            $event->trigger();
            return '1';

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }

    /**
     * Définit les paramètres utilisés par la fonction 'set_has_helped'.
     *
     * @see set_has_helped()
     * @return external_function_parameters
     */
    public static function set_has_helped_parameters() {
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'course id'),
                'id'        => new external_value(PARAM_INT, 'old course id'),
                'hashelped' => new external_value(PARAM_BOOL, 'if has helped')
            )
        );
    }

    /**
     * Définit ce que retourne la fonction 'set_has_helped'.
     *
     * @see set_has_helped()
     * @return external_value
     */
    public static function set_has_helped_returns() {
        return new external_value(PARAM_TEXT, 'validation of set has_helped field');
    }

    /**
     * Remplit le champs 'has_helped' avec la variable $hashelped
     * pour l'id donné en paramètre.
     *
     * @param int     $courseid L'id du cours.
     * @param int     $id L'id de l'interaction.
     * @param int     $hashelped Si cela à aidé l'utilisateur.
     * @return string Un objet avec un champs 'error' ou une simple valeur '1'.
     * @throws RuntimeException
     */
    public static function set_has_helped($courseid, $id, $hashelped) {
        global $DB, $USER;

        // Valide les paramètres.
        $params = external_api::validate_parameters(self::set_has_helped_parameters(), array(
            'courseid' => $courseid,
            'id' => $id,
            'hashelped' => $hashelped
        ));
        $courseid = $params['courseid'];
        $id = $params['id'];
        $hashelped = $params['hashelped'];

        // Valide le contexte.
        try {
            $coursecontext = context_course::instance($courseid, MUST_EXIST);
            external_api::validate_context($coursecontext);
            require_capability('moodle/block:view', $coursecontext);

        } catch (Exception $ex) { // Ne devrait jamais arriver ici normalement.
            //print_error();
            return '{"error": "' . get_string('error_no_authorization_course_view', 'block_chatbot_rasa') . '"}';
        }

        try {
            if ($DB->record_exists(TABLE_INTERACT, array('id' => $id, 'user_id' => $USER->id))) {
                $record = new StdClass();
                $record->id = $id;
                $record->has_helped = $hashelped;
                $DB->update_record(TABLE_INTERACT, $record);
                return '1';

            } else {
                return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
            }

        } catch (RuntimeException $ex) {
            throw ex;
            //return '{"error": "' . get_string('error_not_listed', 'block_chatbot_rasa') . '"}';
        }
    }
}
