<?php
/**
 * {@link https://docs.moodle.org/dev/Coding_style}
 * {@link https://docs.moodle.org/dev/Element_HTML_and_CSS_guidelines}
 */

/**
 * Le block chatbot_rasa, à insérer dans des cours et/ou la page d'accueil.
 *
 * @package    chatbot_rasa
 * @category   block
 * @copyright  2021 José-Paul Blülle Celado
 * @license    ?
 */

require_once("$CFG->dirroot/config.php");

class block_chatbot_rasa extends block_base { // Manque les traductions de cette page avec les 'get_string()'.
    public function init() {
        $this->title = get_string('chatbot_rasa', 'block_chatbot_rasa');
    }

    public function get_content() {
        global $CFG, $COURSE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $stringman = get_string_manager();
        $strings = $stringman->load_component_strings('block_chatbot_rasa', current_language());
        // Chargement des 'get_string()' pour 'module.js'.
        $this->page->requires->strings_for_js(array_keys($strings), 'block_chatbot_rasa');
        $this->page->requires->js(new moodle_url('/blocks/chatbot_rasa/module.js'));

        $coursecontext = context_course::instance($COURSE->id, MUST_EXIST);

        /*
            id :
            m-chat-root

            m-show-chat
            m-show-options
            m-show-questions
            m-reduce-chat

            m-chat-home
            m-list-messages
            m-user-input
            [id of question]

            m-chat-options
            m-add-subject
            m-add-question
            m-add-response
            m-add-confirm
            m-add-result
            m-transfer-course-id
            m-transfer-confirm
            m-transfer-result

            m-chat-questions
            m-search
            m-search-field
            m-list-questions
            m-field-id
            m-field-subject
            m-field-question
            m-field-response
            m-modify-question
            m-delete-question
            m-modify-delete-result
        */

        $this->content = new StdClass();
        // Si l'utilisateur peut modifier le cours et que le cours n'est pas l'accueil.
        if (has_capability('moodle/course:update', $coursecontext) && $COURSE->id > 1) {
            $this->content->text =
            '<div>
                <script type="text/javascript">
                    var courseId = ' . $COURSE->id . ';
                </script>
                <div id="m-chat-root" class="m-element-chat">
                    <div class="m-element-chat__navigate">
                        <input id="m-show-chat" class="m-element-chat__options-button" type="button" value="Chat">
                        <input id="m-show-options" class="m-element-chat__options-button" type="button" value="Options">
                        <input id="m-show-questions" class="m-element-chat__options-button" type="button" value="Questions">
                        <input id="m-reduce-chat" class="m-element-chat__options-button" type="button" value="-">
                    </div>
                    <div id="m-chat-home">
                        <ul id="m-list-messages" class="m-element-chat__messages-list"></ul>
                        <input id="m-user-input" class="m-element-chat__input" type="text">
                    </div>
                    <div id="m-chat-options" class="m-element-chat__options-border-big" hidden>
                    	<div class="m-element-chat__options-create-question">
                            <p class="m-element-chat__options-title"><b>Ajout d\'une nouvelle question</b></p>
                			<label for="m-add-subject" class="m-element-chat__options-label">Sujet : </label>
                			<input id="m-add-subject" class="m-element-chat__options-input" type="text">
                			<label for="m-add-question" class="m-element-chat__options-label">Question : </label>
                			<textarea id="m-add-question" class="m-element-chat__options-input" type="text"></textarea>
                			<label for="m-add-response" class="m-element-chat__options-label" >Réponse : </label>
                			<textarea id="m-add-response" class="m-element-chat__options-input" type="text"></textarea>
                            <span id="m-add-result" hidden></span>
                			<input id="m-add-confirm" class="m-element-chat__options-button" type="button" value="Créer">
                    	</div>
                    	<div class="m-element-chat__options-transfer-question">
                    		<div class="m-element-chat__options-line"></div>
                    		<p class="m-element-chat__options-title"><b>Transfert à ce cours</b></p>
                			<label for="m-transfer-course-id" class="m-element-chat__options-label">Id du cours à hériter : </label>
                			<input id="m-transfer-course-id" class="m-element-chat__options-input" type="number" min="2" step="1">
                            <span id="m-transfer-result" hidden></span>
                			<input id="m-transfer-confirm" class="m-element-chat__options-button" type="button" value="Transférer">
                    	</div>
                    </div>
                    <div id="m-chat-questions" hidden>
                        <div class="m-element-chat__questions-search">
                            <label for="m-search">Rechercher : </label><br>
                            <input id="m-search" class="m-element-chat__questions-search-input" type="text">
                            <select id="m-search-field" class="m-element-chat__questions-search-field">
                                <option value="subject">Sujet</option>
                                <option value="question">Question</option>
                                <option value="response">Réponse</option>
                            </select>
                        </div>
                        <div class="m-element-chat__questions-border m-element-chat__questions-border--list">
                            <span class="m-element-chat__questions-title"><b>Résultats : (Sujets, Questions)</b></span>
                            <div id="m-list-questions"></div>
                        </div>
                        <div class="m-element-chat__questions-border">
                            <span class="m-element-chat__questions-title"><b>Modification / Suppression :</b></span>
                            <span id="m-field-id" hidden></span>
                            <label for="m-field-subject" class="m-element-chat__questions-label">Sujet</label>
                            <input id="m-field-subject" class="m-element-chat__questions-field" type="text">
                            <label for="m-field-question" class="m-element-chat__questions-label">Question</label>
                            <textarea id="m-field-question" class="m-element-chat__questions-field" type="text"></textarea>
                            <label for="m-field-response" class="m-element-chat__questions-label">Réponse</label>
                            <textarea id="m-field-response" class="m-element-chat__questions-field" type="text"></textarea>
                            <span id="m-modify-delete-result" class="m-element-chat__questions-result" hidden></span>
                            <input id="m-modify-question" class="m-element-chat__questions-button" type="button" value="Modifier">
                            <input id="m-delete-question" class="m-element-chat__questions-button" type="button" value="Supprimer">
                            <input id="m-delete-question-all" class="m-element-chat__questions-button" type="button" value="Tout Supprimer !">
                        </div>
                    </div>
                </div>
            </div>';

        } else {
            $this->content->text =
            '<div>
                <script type="text/javascript">
                    var courseId = ' . $COURSE->id . ';
                </script>
                <div id="m-chat-root" class="m-element-chat">
                    <div class="m-element-chat__fullname">
                        <input id="m-reduce-chat" type="button" value="-">
                    </div>
                    <div id="m-chat-home">
                        <ul class="m-element-chat__messages-list" id="m-list-messages"></ul>
                        <input class="m-element-chat__input" id="m-user-input" type="text">
                    </div>
                </div>
            </div>';
        }
        $this->content->footer = '';
        return $this->content;
    }
}
