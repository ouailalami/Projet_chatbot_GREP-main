# This files contains your custom actions which can be used to run
# custom Python code.
#
# See this guide on how to implement these action:
# https://rasa.com/docs/rasa/custom-actions


# This is a simple example for a custom action which utters "Hello World!"

from typing import Any, Text, Dict, List

from rasa_sdk import Action, Tracker
from rasa_sdk.executor import CollectingDispatcher
from rasa_sdk.events import SlotSet, AllSlotsReset
import requests


class ActionGetCourseAnswer(Action):

    def name(self) -> Text:
        return "get_course_question"    #Nom de l'action

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:

        course_subject = next(tracker.get_latest_entity_values("course_subject"), None) #Récupération du sujet du cours dans le messaage
        module_number = tracker.get_slot('course_module_number')    #Récupération du numéro de module dans moodle

        #Si le sujet n'a pas pu être récupèrer
        if course_subject is None:
            user_message = tracker.latest_message['text']   #Récupération du dernier message de l'utilisateurs
            user_message_split = user_message.split()       #Séparation du message par espace blanc
            for i in range(0, len(user_message_split)):     #Pour chaque mot
                user_message_split[i] = { 'title': user_message_split[i], 'payload': '/give_course_subject{"course_subject":"' + user_message_split[i] + '"}' } #Création de bouton pour chaque mot

            dispatcher.utter_message(text='Je n\'ai pas réussis à récupérer le sujet, pouvez-vous le sélectionner :', buttons=user_message_split);              #Envoi à l'utilisateur
        else:
            token = '158391fcd50670ced0c84dbf1dfbdafd'      #Token permettant d'utiliser les fonctions php dans le plugin
            funct = 'moodle_chatbot_rasa_get_question_rasa' #Fonction dans le plugin
            link = "http://127.0.0.1/webservice/rest/server.php?wstoken={}&wsfunction={}&moodlewsrestformat=json&courseid={}&subject={}&question=".format(token, funct, int(module_number), course_subject)
            r = requests.get(link)  #Création et envoi de la requête
            result = r.json()       #Transformation en json de la réponse

            # Si on demande un sujet avec une seule question disponible dans la bdd.
            if len(result) == 1:
                # On cherche la réponse à la question
                link = "http://127.0.0.1/webservice/rest/server.php?wstoken={}&wsfunction={}&moodlewsrestformat=json&courseid={}&subject={}&question=".format(token, funct, int(module_number), course_subject, result[0])
                r = requests.get(link)
                response = r.json()
                dispatcher.utter_message(response='utter_answer_to_question', answer=response[0])
            # Si on demande un sujet avec plusieurs questions disponibles dans la bdd.
            elif len(result) > 1:
                #Pour chaque question
                for i in range(0, len(result)):
                    result[i] = { 'title': result[i], 'payload': course_subject}    #Création de boutons avec payload contenant le sujet du cours
                dispatcher.utter_message(response="utter_multiple_questions_to_subject", buttons=result)    #Envoi du message à l'utilisateur
            else:
                dispatcher.utter_message(text="Malheureusement je n'ai aucune réponse à propos de ce sujet, contacte l'assistant et il se réjouira de t'aider ;)")  #Si aucune questions ou réponse est disponible

        return []

class ActionAskAssistant(Action):

    def name(self) -> Text:
        return "action_ask_assistant"

    def run(self, dispatcher: CollectingDispatcher,
            tracker: Tracker,
            domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:
        #Récupérer le mail de l'assistant et envoyer un message.
        dispatcher.utter_message(text="Message envoyé à l'assistant :)")    #Message renvoyé à l'utilisateur.

        return []


class ActionResetSlots(Action):
    # Fonction qui remet à zéro tous les slots qui ont été affecté lors de la conversation
    # Cette fonction est appelée lors d'un au revoir de la part de l'utilisateur.
     def name(self) -> Text:
            return "action_reset_slots"

     def run(self, dispatcher: CollectingDispatcher,
             tracker: Tracker,
             domain: Dict[Text, Any]) -> List[Dict[Text, Any]]:

         return [AllSlotsReset()]
