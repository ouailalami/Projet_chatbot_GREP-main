<?php
/**
 * {@link https://docs.moodle.org/dev/Coding_style}
 * {@link https://mahakala.lesc.uec.ac.jp/mahoodle/moodle/docs/34/ja/dev/Adding_a_web_service_to_a_plugin.html#dev-adding-a-web-service-to-a-plugin}
 */

$services = array(
    'Service Chatbot Rasa - for ajax' => array(
        'functions' => array(
            'moodle_chatbot_rasa_post_message',
            'moodle_chatbot_rasa_create_question',
            'moodle_chatbot_rasa_modify_question',
            'moodle_chatbot_rasa_delete_question',
            'moodle_chatbot_rasa_get_question',
            'moodle_chatbot_rasa_transfer_question',
            'moodle_chatbot_rasa_trigger_welcome',
            'moodle_chatbot_rasa_set_has_helped'
        ),
        'requiredcapability' => 'moodle/course:view', // moodle/site:manageblocks
        'restrictedusers'    => 0,
        'enabled'            => 1
    ),

    'Service Chatbot Rasa - for Rasa' => array(
        'functions'          => array('moodle_chatbot_rasa_get_question_rasa'),
        'requiredcapability' => 'webservice/rest:use',
        'restrictedusers'    => 1,
        'enabled'            => 1,
        'shortname'          => 'servicerasarestapi'
    )
);

$functions = array(
    'moodle_chatbot_rasa_post_message' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'post_message',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'send text to Rasa, get response and write the interaction on the database',
        'type'        => 'write',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_create_question' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'create_question',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'insert a question on the database for Rasa',
        'type'        => 'write',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_transfer_question' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'transfer_old_course',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'transfer question from old course to new course',
        'type'        => 'write',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_trigger_welcome' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'trigger_welcome',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'get the welcome message from Rasa',
        'type'        => 'read',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_modify_question' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'modify_question',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'modify question from database',
        'type'        => 'write',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_delete_question' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'delete_question',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'delete question from database',
        'type'        => 'write',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_get_question' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'get_question',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'get question on the database',
        'type'        => 'read',
        'ajax'        => true
    ),

    'moodle_chatbot_rasa_get_question_rasa' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'get_question_rasa',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'get question on the database for Rasa',
        'type'        => 'read',
        'ajax'        => false
    ),

    'moodle_chatbot_rasa_set_has_helped' => array(
        'classname'   => 'moodle_external_chatbot_rasa',
        'methodname'  => 'set_has_helped',
        'classpath'   => 'blocks/chatbot_rasa/component/external/externallib.php',
        'description' => 'set the field has_helped of the interaction\'s id',
        'type'        => 'write',
        'ajax'        => true
    )
);
